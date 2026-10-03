<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Send test email" on Settings → Notifications (owner only, M8).
 */
class TestEmailController extends Controller
{
    /**
     * Queues a test email to the signed-in owner.
     */
    public function __invoke(Request $request, NotificationService $notifications): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notifications->sendTest($user);

        return back()->with('success', "Test email queued for {$user->email}. It arrives once the queue worker has run; failures appear in the activity log.");
    }
}
