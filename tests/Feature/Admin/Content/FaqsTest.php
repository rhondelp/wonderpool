<?php

/*
| M3: FAQ CRUD, search and reorder.
*/

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Faq;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->owner()->create());
});

it('lists and searches questions and answers', function () {
    Faq::factory()->create(['question' => 'Can we bring food?', 'answer' => 'Yes, no corkage.']);
    Faq::factory()->create(['question' => 'Is there parking?', 'answer' => 'Yes, for 20 cars.']);

    $this->get('/admin/faqs')->assertOk()->assertSee('Can we bring food?')->assertSee('Is there parking?');
    $this->get('/admin/faqs?q=CORKAGE')->assertSee('Can we bring food?')->assertDontSee('Is there parking?');
});

it('creates an FAQ at the end of the list', function () {
    Faq::factory()->create(['sort_order' => 3]);

    $this->post('/admin/faqs', ['question' => 'What time is check-in?', 'answer' => '7:00 AM for day packages.', 'is_active' => '1'])
        ->assertRedirect('/admin/faqs');

    $faq = Faq::query()->where('question', 'What time is check-in?')->sole();
    expect($faq->sort_order)->toBe(4)
        ->and(ActivityLog::query()->where('action', ActivityAction::ContentCreated->value)->value('properties'))->toMatchArray(['type' => 'Faq']);
});

it('validates FAQ input', function (array $input, string $field) {
    $this->post('/admin/faqs', $input + ['question' => 'Q?', 'answer' => 'A.'])->assertSessionHasErrors($field);
})->with([
    'missing question' => [['question' => ''], 'question'],
    'missing answer' => [['answer' => ''], 'answer'],
    'question too long' => [['question' => str_repeat('a', 256)], 'question'],
]);

it('updates and deletes an FAQ', function () {
    $faq = Faq::factory()->create();

    $this->put("/admin/faqs/{$faq->id}", ['question' => 'Updated?', 'answer' => 'Yes.', 'is_active' => '0', 'sort_order' => '9'])
        ->assertRedirect('/admin/faqs');
    expect($faq->fresh()->only(['question', 'is_active', 'sort_order']))->toBe(['question' => 'Updated?', 'is_active' => false, 'sort_order' => 9]);

    $this->delete("/admin/faqs/{$faq->id}")->assertRedirect('/admin/faqs');
    expect(Faq::query()->count())->toBe(0);
});

it('keeps the order when sort_order is left blank on update', function () {
    $faq = Faq::factory()->create(['sort_order' => 5]);

    $this->put("/admin/faqs/{$faq->id}", ['question' => 'Q?', 'answer' => 'A.', 'is_active' => '1', 'sort_order' => '']);

    expect($faq->fresh()->sort_order)->toBe(5);
});

it('reorders FAQs and logs it', function () {
    [$a, $b] = Faq::factory()->count(2)->sequence(['sort_order' => 1], ['sort_order' => 2])->create();

    $this->patchJson('/admin/faqs/reorder', ['ids' => [$b->id, $a->id]])->assertOk();

    expect(Faq::query()->ordered()->pluck('id')->all())->toBe([$b->id, $a->id])
        ->and(ActivityLog::query()->where('action', ActivityAction::ContentReordered->value)->value('properties'))->toBe(['type' => 'Faq', 'count' => 2]);
});

it('rejects invalid reorder payloads', function (array $payload) {
    Faq::factory()->create();

    $this->patchJson('/admin/faqs/reorder', $payload)->assertUnprocessable();
})->with([
    'missing ids' => [[]],
    'not integers' => [['ids' => ['a', 'b']]],
    'duplicates' => [['ids' => [1, 1]]],
    'unknown id' => [['ids' => [999999]]],
]);
