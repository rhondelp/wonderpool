<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes append-only audit entries to activity_logs ("who did what and when").
 * The actor defaults to the signed-in user; pass one explicitly for logins or system jobs.
 */
class ActivityLogger
{
    /**
     * @param  AuthFactory  $auth  Used to resolve the default actor
     */
    public function __construct(private readonly AuthFactory $auth)
    {
    }

    /**
     * Records one action.
     *
     * @param  ActivityAction  $action  What happened
     * @param  Model|null  $subject  Record acted on (stored as a morph reference)
     * @param  array<string, mixed>  $properties  Extra context, e.g. changed values; never passwords
     * @param  User|null  $actor  Who did it (default: signed-in user, null for guests/system)
     * @return ActivityLog The stored entry
     */
    public function log(ActivityAction $action, ?Model $subject = null, array $properties = [], ?User $actor = null): ActivityLog
    {
        $actorId = $actor !== null ? $actor->getKey() : $this->auth->guard()->id();

        return ActivityLog::query()->create([
            'user_id' => $actorId,
            'action' => $action->value,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
        ]);
    }
}
