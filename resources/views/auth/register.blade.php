<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First-Time Setup — Mang Takyu</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>

<body class="min-h-screen bg-[#0B0B0B] relative overflow-hidden">

    {{-- Red glow accents --}}
    <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-[#E31E24]/30 blur-3xl"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-[#F5A623]/20 blur-3xl"></div>

    <div class="relative min-h-screen flex items-center justify-center px-4 py-10">

        <div class="w-full max-w-md">

            {{-- Brand --}}
            <div class="flex flex-col items-center mb-6">
                <div class="w-20 h-20 rounded-2xl bg-[#E31E24] flex items-center justify-center shadow-2xl shadow-red-900/40">
                    <span class="text-white font-extrabold text-3xl">MT</span>
                </div>
                <h1 class="mt-4 text-2xl font-extrabold text-white tracking-widest">MANG TAKYU</h1>
                <p class="text-xs uppercase tracking-widest text-[#F5A623] mt-1">
                    First-Time Setup
                </p>
            </div>

            {{-- Setup notice --}}
            <div class="mb-5 rounded-xl bg-[#F5A623]/10 border border-[#F5A623]/30 px-4 py-3 text-sm text-[#F5A623]">
                <strong>One-time setup:</strong> Create the first Manager account.
                This page will be disabled after you submit.
            </div>

            {{-- Card --}}
            <div class="rounded-2xl bg-white/[0.04] backdrop-blur-xl border border-white/10 p-8 shadow-2xl">

                @if ($errors->any())
                    <div class="mb-5 rounded-lg bg-red-500/20 border border-red-500/40 px-4 py-3 text-sm text-red-100">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-xs uppercase tracking-wider text-white/60 mb-2">
                            Full Name
                        </label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}"
                               required autofocus autocomplete="name"
                               placeholder="Emy Joy Suarez"
                               class="w-full rounded-lg bg-black/40 border border-white/10 px-4 py-3 text-white
                                      placeholder:text-white/30 focus:outline-none focus:ring-2
                                      focus:ring-[#E31E24] focus:border-[#E31E24] transition">
                    </div>

                    <div>
                        <label for="email" class="block text-xs uppercase tracking-wider text-white/60 mb-2">
                            Email
                        </label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}"
                               required autocomplete="username"
                               placeholder="manager@mangtakyu.com"
                               class="w-full rounded-lg bg-black/40 border border-white/10 px-4 py-3 text-white
                                      placeholder:text-white/30 focus:outline-none focus:ring-2
                                      focus:ring-[#E31E24] focus:border-[#E31E24] transition">
                    </div>

                    <div>
                        <label for="password" class="block text-xs uppercase tracking-wider text-white/60 mb-2">
                            Password
                        </label>
                        <input id="password" name="password" type="password"
                               required autocomplete="new-password"
                               placeholder="At least 6 characters"
                               class="w-full rounded-lg bg-black/40 border border-white/10 px-4 py-3 text-white
                                      placeholder:text-white/30 focus:outline-none focus:ring-2
                                      focus:ring-[#E31E24] focus:border-[#E31E24] transition">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs uppercase tracking-wider text-white/60 mb-2">
                            Confirm Password
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               required autocomplete="new-password"
                               placeholder="Repeat your password"
                               class="w-full rounded-lg bg-black/40 border border-white/10 px-4 py-3 text-white
                                      placeholder:text-white/30 focus:outline-none focus:ring-2
                                      focus:ring-[#E31E24] focus:border-[#E31E24] transition">
                    </div>

                    <button type="submit"
                            class="w-full rounded-lg bg-[#E31E24] hover:bg-[#c41a1f] py-3.5 font-bold
                                   tracking-widest text-white transition shadow-lg shadow-red-900/40">
                        CREATE MANAGER ACCOUNT
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-white/40">
                Already have an account?
                <a href="{{ route('login') }}" class="text-[#F5A623] hover:underline font-semibold">
                    Log in
                </a>
            </p>
        </div>
    </div>
</body>
</html>