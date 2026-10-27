@extends('layouts.app')

@section('title', 'Account Settings')
@section('page-title', 'Account Settings')
@section('page-subtitle', 'Manage your account security.')

@section('content')
<div class="mx-auto max-w-2xl space-y-5">
    <section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
    <h2 class="text-lg font-bold text-slate-800">Account</h2>
    <p class="mt-1 text-sm text-slate-500">Update your name and email address.</p>

    <form method="POST" action="{{ route('account-settings.update') }}" class="mt-6 space-y-5" autocomplete="off">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Full Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                   required maxlength="255"
                   class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email Address</label>
            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                   required maxlength="255"
                   class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="current_password" class="mb-2 block text-sm font-semibold text-slate-700">Current Password</label>
            <input type="password" name="current_password" id="current_password" required autocomplete="current-password"
                   class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
            @error('current_password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="rounded-xl bg-[#E31E24] px-5 py-3 font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
            Save Changes
        </button>
    </form>
</section>

<section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
    <h2 class="text-lg font-bold text-slate-800">Change Password</h2>
    <p class="mt-1 text-sm text-slate-500">Leave blank to keep your current password.</p>

    <form method="POST" action="{{ route('account-settings.update') }}" class="mt-6 space-y-5" autocomplete="off">
        @csrf
        @method('PUT')

        <div>
            <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">New Password</label>
            <input type="password" name="password" id="password" minlength="8" autocomplete="new-password"
                   class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">Confirm New Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" minlength="8" autocomplete="new-password"
                   class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
            @error('password_confirmation')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="current_password" class="mb-2 block text-sm font-semibold text-slate-700">Current Password (required to confirm)</label>
            <input type="password" name="current_password" id="current_password" required autocomplete="current-password"
                   class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
            @error('current_password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="rounded-xl bg-[#E31E24] px-5 py-3 font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
            Update Password
        </button>
    </form>
</section>
</div>
@endsection
