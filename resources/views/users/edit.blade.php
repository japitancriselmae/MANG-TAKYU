@extends('layouts.app')

@section('title', 'Edit User Account')
@section('page-title', 'Edit User Account')
@section('page-subtitle', 'Update login account details.')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">

        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                       required maxlength="255"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                       required maxlength="255"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl
                              focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <p class="text-xs text-slate-500 mb-3">
                    <strong class="text-slate-700">Leave password blank</strong> to keep the current password.
                </p>

                <div class="space-y-3">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">
                            New Password (optional)
                        </label>
                        <input type="password" name="password" id="password"
                               placeholder="At least 6 characters"
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                      focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-2">
                            Confirm New Password
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               placeholder="Repeat the new password"
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl
                                      focus:outline-none focus:border-[#E31E24] focus:ring-2 focus:ring-[#E31E24]/20 transition">
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold
                               shadow-lg shadow-red-500/20 transition">
                    Update Account
                </button>
                <a href="{{ route('users.index') }}"
                   class="px-6 py-3 rounded-xl border border-slate-200 text-slate-700 font-semibold
                          hover:bg-slate-50 transition">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>

@endsection