<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function edit(): View
    {
        return view('account-settings.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['required', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $oldName = $user->name;
        $name = $validated['name'] ?? $user->name;
        $email = strtolower($validated['email'] ?? $user->email);
        $emailChanged = $user->email !== $email;
        $passwordChanged = ! empty($validated['password']);

        DB::transaction(function () use ($user, $name, $email, $emailChanged, $passwordChanged, $oldName, $activityLogger, $validated): void {
            $user->name = $name;
            $user->email = $email;

            if ($passwordChanged) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            $activityLogger->record(
                action: 'account.settings_updated',
                module: 'users',
                description: 'Account settings updated.',
                subject: $user,
                oldValues: [
                    'name' => $oldName,
                    'email_changed' => false,
                    'password_changed' => false,
                ],
                newValues: [
                    'name' => $user->name,
                    'email_changed' => $emailChanged,
                    'password_changed' => $passwordChanged,
                ],
            );
        });

        $request->session()->regenerate();

        return redirect()
            ->route('account-settings.edit')
            ->with(
                'success',
                $passwordChanged
                    ? 'Your password has been changed successfully.'
                    : 'Your account has been updated successfully.'
            );
    }
}
