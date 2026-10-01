<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Owner-only business settings, one tab per SettingGroup.
 */
class SettingController extends Controller
{
    /**
     * Form for one group.
     */
    public function edit(SettingGroup $group, SettingService $settings): View
    {
        return view('admin.settings.edit', [
            'group' => $group,
            'groups' => SettingGroup::cases(),
            'values' => $settings->group($group),
        ]);
    }

    /**
     * Saves the group's values.
     */
    public function update(UpdateSettingsRequest $request, SettingGroup $group, SettingService $settings): RedirectResponse
    {
        $changes = $settings->update($group, $request->settings());

        return redirect()
            ->route('admin.settings.edit', $group)
            ->with('success', $changes === [] ? 'No changes to save.' : $group->label().' settings saved.');
    }
}
