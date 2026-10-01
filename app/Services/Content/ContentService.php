<?php

namespace App\Services\Content;

use App\Enums\ActivityAction;
use App\Exceptions\ContentInUseException;
use App\Models\Contracts\GuardsDeletion;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Generic create/update/delete/reorder for admin content modules (packages, add-ons,
 * amenities, gallery, FAQs). Every change is written to the activity log (D-019).
 * Modules with files (amenities, gallery) wrap this in their own service.
 */
class ContentService
{
    /**
     * @param  ActivityLogger  $logger  Audit trail
     */
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    /**
     * Creates a record. Sortable models without a sort_order go to the end of the list.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $class  Model class
     * @param  array<string, mixed>  $data  Validated attributes
     * @return TModel
     */
    public function create(string $class, array $data): Model
    {
        $model = new $class();

        if ($this->isSortable($model) && ($data['sort_order'] ?? null) === null) {
            $data['sort_order'] = ((int) $class::query()->max('sort_order')) + 1;
        }

        $model->fill($data)->save();
        $this->logger->log(ActivityAction::ContentCreated, $model, $this->context($model));

        return $model;
    }

    /**
     * Updates a record and logs the names of the changed fields (not their values).
     *
     * @template TModel of Model
     *
     * @param  TModel  $model  Record to change
     * @param  array<string, mixed>  $data  Validated attributes
     * @return TModel
     */
    public function update(Model $model, array $data): Model
    {
        if (array_key_exists('sort_order', $data) && $data['sort_order'] === null) {
            unset($data['sort_order']);
        }

        $model->fill($data);
        $changed = array_keys($model->getDirty());

        if ($changed !== []) {
            $model->save();
            $this->logger->log(ActivityAction::ContentUpdated, $model, $this->context($model) + ['fields' => $changed]);
        }

        return $model;
    }

    /**
     * Deletes a record unless it refuses (GuardsDeletion).
     *
     * @throws ContentInUseException When the record is still referenced
     */
    public function delete(Model $model): void
    {
        if ($model instanceof GuardsDeletion && ($reason = $model->deletionBlockedReason()) !== null) {
            throw new ContentInUseException($reason);
        }

        $context = $this->context($model);
        $model->delete();
        $this->logger->log(ActivityAction::ContentDeleted, $model, $context);
    }

    /**
     * Applies a new order for some records of a sortable table.
     * $ids may be a subset (one page, a filtered list): those records keep the slots they
     * occupied in the full ordering and are rearranged among them; every row is then
     * renumbered 1..n, which also repairs duplicate sort_order values.
     *
     * @param  class-string<Model>  $class  Model with a sort_order column
     * @param  list<int>  $ids  Record ids in their new order
     *
     * @throws ValidationException When an id does not exist
     */
    public function reorder(string $class, array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        DB::transaction(function () use ($class, $ids): void {
            /** @var list<int> $all */
            $all = $class::query()->orderBy('sort_order')->orderBy('id')->lockForUpdate()->pluck('id')->map(fn ($id): int => (int) $id)->all();

            if (array_diff($ids, $all) !== []) {
                throw ValidationException::withMessages(['ids' => 'Some items no longer exist. Reload the page and try again.']);
            }

            $slots = array_keys(array_intersect($all, $ids));
            foreach ($slots as $i => $position) {
                $all[$position] = $ids[$i];
            }

            foreach ($all as $index => $id) {
                $class::query()->whereKey($id)->where('sort_order', '!=', $index + 1)->update(['sort_order' => $index + 1]);
            }
        });

        $this->logger->log(ActivityAction::ContentReordered, null, ['type' => class_basename($class), 'count' => count($ids)]);
    }

    /**
     * Activity log context: model type and label.
     *
     * @return array{type: string, label: string}
     */
    private function context(Model $model): array
    {
        $label = method_exists($model, 'adminLabel') ? $model->adminLabel() : '#'.$model->getKey();

        return ['type' => class_basename($model), 'label' => (string) $label];
    }

    /**
     * Whether the model's table has a sort_order column (it is fillable).
     */
    private function isSortable(Model $model): bool
    {
        return $model->isFillable('sort_order');
    }
}
