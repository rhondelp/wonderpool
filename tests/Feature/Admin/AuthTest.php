<?php

/*
| M2: admin sign-in/out, disabled accounts, rate limiting and the forced password change.
*/

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('shows the login form to guests and redirects them away from the admin', function () {
    $this->get('/admin/login')->assertOk()->assertSee('Sign in to the admin panel');
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('signs in an active user with any email casing and records the login', function () {
    $user = User::factory()->create(['email' => 'staff@example.com']);

    $this->post('/admin/login', ['email' => ' Staff@Example.COM ', 'password' => 'password'])
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull()
        ->and(ActivityLog::query()->where('action', ActivityAction::Login->value)->where('user_id', $user->id)->exists())->toBeTrue();
});

it('rejects a wrong password', function () {
    $user = User::factory()->create();

    $this->from('/admin/login')
        ->post('/admin/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertRedirect('/admin/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects disabled accounts at sign-in', function () {
    $user = User::factory()->inactive()->create();

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('locks out after five failed attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toStartWith('Too many login attempts');
    $this->assertGuest();
});

it('signs out and records the logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/admin/logout')->assertRedirect('/admin/login');

    $this->assertGuest();
    expect(ActivityLog::query()->where('action', ActivityAction::Logout->value)->exists())->toBeTrue();
});

it('redirects signed-in users away from the login form', function () {
    $this->actingAs(User::factory()->create())->get('/admin/login')->assertRedirect('/admin');
});

it('signs out a user who was disabled mid-session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $user->forceFill(['is_active' => false])->save();

    $this->get('/admin')->assertRedirect('/admin/login')->assertSessionHas('error');
    $this->assertGuest();
});

it('forces a password change before any admin page', function () {
    $user = User::factory()->owner()->mustChangePassword()->create();

    $this->actingAs($user)->get('/admin')->assertRedirect('/admin/password');
    $this->actingAs($user)->get('/admin/settings/general')->assertRedirect('/admin/password');
    $this->actingAs($user)->get('/admin/password')->assertOk()->assertDontSee('Back to dashboard');
});

it('changes the own password and clears the forced-change flag', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)->put('/admin/password', [
        'current_password' => 'password',
        'password' => 'new-secret-123',
        'password_confirmation' => 'new-secret-123',
    ])->assertRedirect('/admin')->assertSessionHas('success');

    $user->refresh();
    expect(Hash::check('new-secret-123', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeFalse()
        ->and(ActivityLog::query()->where('action', ActivityAction::PasswordChanged->value)->exists())->toBeTrue();

    $this->get('/admin')->assertOk();
});

it('validates the password change', function (array $input, string $errorField) {
    $user = User::factory()->create(['password' => 'secret-123']);

    $this->actingAs($user)->put('/admin/password', $input)->assertSessionHasErrors($errorField);
})->with([
    'wrong current password' => [['current_password' => 'nope', 'password' => 'new-secret-123', 'password_confirmation' => 'new-secret-123'], 'current_password'],
    'too short' => [['current_password' => 'secret-123', 'password' => 'abc12', 'password_confirmation' => 'abc12'], 'password'],
    'no numbers' => [['current_password' => 'secret-123', 'password' => 'onlyletters', 'password_confirmation' => 'onlyletters'], 'password'],
    'not confirmed' => [['current_password' => 'secret-123', 'password' => 'new-secret-123', 'password_confirmation' => 'other-123'], 'password'],
    'same as current' => [['current_password' => 'secret-123', 'password' => 'secret-123', 'password_confirmation' => 'secret-123'], 'password'],
]);
