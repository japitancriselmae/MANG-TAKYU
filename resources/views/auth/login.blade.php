<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Mang Takyu</title>

    <script src="{{ asset('js/tailwind.js') }}"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Fix Chrome autofill white/dark background */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px rgba(0,0,0,0.4) inset !important;
            -webkit-text-fill-color: #ffffff !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>

<body class="min-h-screen bg-[#0B0B0B] relative overflow-hidden">

    {{-- Red glow --}}
    <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-[#E31E24]/30 blur-3xl"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-[#F5A623]/20 blur-3xl"></div>

    <div class="relative min-h-screen flex items-center justify-center px-4 py-10">

        <div class="w-full max-w-sm">

            {{-- Logo --}}
            <div class="flex flex-col items-center mb-6">
                <div class="w-40 h-40 rounded-3xl overflow-hidden ring-4 ring-[#E31E24] shadow-2xl shadow-red-900/50 bg-white p-2 flex items-center justify-center">
                    <img src="{{ asset('images/mang-takyu-logo.png') }}"
                         alt="Mang Takyu"
                         class="w-full h-full object-contain">
                </div>
                <h1 class="mt-4 text-2xl font-extrabold text-white tracking-widest">MANG TAKYU</h1>
                <p class="text-xs uppercase tracking-widest text-[#F5A623] mt-1">
                    Management System
                </p>
            </div>

            {{-- Card --}}
            <div class="rounded-2xl bg-white/[0.04] backdrop-blur-xl border border-white/10 p-8 shadow-2xl">

                @if ($errors->any())
                    <div class="mb-5 rounded-lg bg-red-500/20 border border-red-500/40 px-4 py-3 text-sm text-red-100 text-center">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-4" autocomplete="off">

                    @csrf

                    {{-- Chrome autofill decoy fields --}}
                    <input type="text"     name="fakeusernameremembered" style="display:none">
                    <input type="password" name="fakepasswordremembered" style="display:none">

                    <div>
                        <label for="email" class="block text-xs uppercase tracking-wider text-white/60 mb-2">Email</label>
                        <input id="email" name="email" type="email"
                               value="{{ old('email') }}"
                               required autofocus
                               autocomplete="off"
                               class="w-full rounded-lg bg-black/40 border border-white/10 px-4 py-3 text-white
                                      placeholder:text-white/30 focus:outline-none focus:ring-2
                                      focus:ring-[#E31E24] focus:border-[#E31E24] transition"
                               placeholder="Enter your email">
                    </div>

                    <div>
                        <label for="password" class="block text-xs uppercase tracking-wider text-white/60 mb-2">Password</label>
                        <input id="password" name="password" type="password"
                               required
                               autocomplete="new-password"
                               class="w-full rounded-lg bg-black/40 border border-white/10 px-4 py-3 text-white
                                      placeholder:text-white/30 focus:outline-none focus:ring-2
                                      focus:ring-[#E31E24] focus:border-[#E31E24] transition"
                               placeholder="Enter your password">
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 text-white/70 cursor-pointer">
                            <input type="checkbox" name="remember"
                                   class="h-4 w-4 rounded border-white/20 bg-black/40 text-[#E31E24] focus:ring-[#E31E24]">
                            Remember me
                        </label>
                    </div>

                    <button type="submit"
                            class="w-full rounded-lg bg-[#E31E24] hover:bg-[#c41a1f] py-3.5 font-bold
                                   tracking-widest text-white transition shadow-lg shadow-red-900/40">
                        LOGIN
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-white/40">
                Need to create the first account?
                <a href="{{ route('register') }}" class="text-[#F5A623] hover:underline font-semibold">
                    First-time setup
                </a>
            </p>
            <p class="mt-4 text-center text-xs text-white/30">
                &copy; {{ date('Y') }} Mang Takyu · Arellano St., Tagum City
            </p>
        </div>
    </div>
</body>
</html>