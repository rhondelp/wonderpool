<?php

/*
| M2: owner-only user management (create, edit, enable/disable, temporary passwords).
*/

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->owner = User::factory()->owner()->create(['name' => 'Olivia Owner']);
});

it('lists users', function () {
    $staff = User::factory()->create(['name' => 'Sam Staff']);

    $this->actingAs($this->owner)->get('/admin/users')
        ->assertOk()
        ->assertSee('Olivia Owner')
        ->assertSee('Sam Staff')
        ->assertSee(route('admin.users.toggle-active', $staff))
        ->assertDontSee(route('admin.users.toggle-active', $this->owner));
});

it('creates a staff user with a temporary password', function () {
    $this->actingAs($this->owner)->post('/admin/users', [
        'name' => 'Juan Dela Cruz',
        'email' => 'Juan@Example.com',
        'role' => 'staff',
        'password' => 'temp-pass-123',
        'password_confirmation' => 'temp-pass-123',
    ])->assertRedirect('/admin/users')->assertSessionHas('success');

    $user = User::query()->where('email', 'juan@example.com')->sole();
    expect($user->role)->toBe(UserRole::Staff)
        ->and($user->is_active)->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and(Hash::check('temp-pass-123', $user->password))->toBeTrue()
        ->and(ActivityLog::query()->where('action', ActivityAction::UserCreated->value)->where('subject_id', $user->id)->exists())->toBeTrue();
});

it('rejects a duplicate email regardless of case', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->owner)->post('/admin/users', [
        'name' => 'Someone',
        'email' => 'TAKEN@example.com',
        'role' => 'staff',
        'password' => 'temp-pass-123',
        'password_confirmation' => 'temp-pass-123',
    ])->assertSessionHasErrors('email');
});

it('updates a user and logs the changed fields', function () {
    $staff = User::factory()->create();

    $this->actingAs($this->owner)->put("/admin/users/{$staff->id}", [
        'name' => 'Renamed Staff',
        'email' => $staff->email,
        'role' => 'owner',
    ])->assertRedirect('/admin/users');

    expect($staff->fresh()->name)->toBe('Renamed Staff')
        ->and($staff->fresh()->role)->toBe(UserRole::Owner);

    $log = ActivityLog::query()->where('action', ActivityAction::UserUpdated->value)->sole();
    expect($log->properties['fields'])->toEqualCanonicalizing(['name', 'role']);
});

it('does not let owners change their own role', function () {
    $this->actingAs($this->owner)->put("/admin/users/{$this->owner->id}", [
        'name' => 'Olivia Owner',
        'email' => $this->owner->email,
        'role' => 'staff',
    ])->assertSessionHasErrors(['role' => 'You cannot change your own role.']);

    expect($this->owner->fresh()->role)->toBe(UserRole::Owner);
});

it('disables and re-enables another user', function () {
    $staff = User::factory()->create();

    $this->actingAs($this->owner)->patch("/admin/users/{$staff->id}/active")->assertRedirect();
    expect($staff->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->owner)->patch("/admin/users/{$staff->id}/active");
    expect($staff->fresh()->is_active)->toBeTrue()
        ->and(ActivityLog::query()->whereIn('action', [ActivityAction::UserDeactivated->value, ActivityAction::UserActivated->value])->count())->toBe(2);
});

it('does not let owners disable or reset themselves', function () {
    $this->actingAs($this->owner)->patch("/admin/users/{$this->owner->id}/active")->assertForbidden();
    $this->actingAs($this->owner)->put("/admin/users/{$this->owner->id}/password", [
        'password' => 'temp-pass-123',
        'password_confirmation' => 'temp-pass-123',
    ])->assertForbidden();

    expect($this->owner->fresh()->is_active)->toBeTrue();
});

it('sets a temporary password that must be changed', function () {
    $staff = User::factory()->create();

    $this->actingAs($this->owner)->put("/admin/users/{$staff->id}/password", [
        'password' => 'temp-pass-456',
        'password_confirmation' => 'temp-pass-456',
    ])->assertRedirect('/admin/users');

    $staff->refresh();
    expect(Hash::check('temp-pass-456', $staff->password))->toBeTrue()
        ->and($staff->must_change_password)->toBeTrue();

    $log = ActivityLog::query()->where('action', ActivityAction::UserPasswordReset->value)->sole();
    expect($log->properties)->toBeNull();
});

it('renders the create and edit forms', function () {
    $staff = User::factory()->create();

    $this->actingAs($this->owner)->get('/admin/users/create')->assertOk()->assertSee('Temporary password');
    $this->actingAs($this->owner)->get("/admin/users/{$staff->id}/edit")->assertOk()->assertSee('Set temporary password');
    $this->actingAs($this->owner)->get("/admin/users/{$this->owner->id}/edit")->assertOk()->assertSee('you cannot change your own role');
});
