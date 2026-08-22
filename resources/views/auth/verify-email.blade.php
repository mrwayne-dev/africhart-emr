@extends('layouts.guest')

@section('title', 'Verify your email — AfriChart EMR')

@section('content')
    <div class="bg-page border border-line rounded-card p-8">
        <div class="mb-6 text-center">
            <div class="w-12 h-12 bg-warm rounded-full flex items-center justify-center mx-auto mb-4">
                <x-phosphor-envelope-simple class="w-6 h-6 text-ink" />
            </div>
            <h1 class="text-xl font-medium text-ink tracking-tight">Verify your email</h1>
            <p class="text-sm text-muted mt-1">
                We emailed a 6-digit code to<br>
                <span class="text-ink-body font-medium">{{ auth()->user()->email }}</span>
            </p>
        </div>

        {{--
            Six boxes rather than one field.

            The single input still exists — it is the one that posts, hidden
            below — because the boxes are a presentation of it, not six values to
            reassemble server-side. That keeps the controller untouched and means
            a browser autofilling a one-time code, or someone pasting all six
            digits at once, both work without special cases.
        --}}
        <form method="POST" action="{{ route('verification.verify') }}" class="space-y-5"
            x-data="{
                loading: false,
                digits: ['', '', '', '', '', ''],

                get code() { return this.digits.join('') },

                onInput(i, event) {
                    // Keep the last digit typed, so overtyping a filled box works.
                    const value = event.target.value.replace(/\D/g, '');
                    this.digits[i] = value.slice(-1);
                    event.target.value = this.digits[i];

                    if (this.digits[i] && i < 5) this.$refs['box' + (i + 1)].focus();
                },

                onKeydown(i, event) {
                    if (event.key === 'Backspace' && ! this.digits[i] && i > 0) {
                        this.$refs['box' + (i - 1)].focus();
                    }
                    if (event.key === 'ArrowLeft' && i > 0) this.$refs['box' + (i - 1)].focus();
                    if (event.key === 'ArrowRight' && i < 5) this.$refs['box' + (i + 1)].focus();
                },

                onPaste(event) {
                    const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                    if (! pasted) return;

                    event.preventDefault();
                    for (let i = 0; i < 6; i++) {
                        this.digits[i] = pasted[i] ?? '';
                        this.$refs['box' + i].value = this.digits[i];
                    }
                    // Land on the first empty box, or the last if it filled.
                    const next = Math.min(pasted.length, 5);
                    this.$refs['box' + next].focus();
                },
            }"
            @submit="loading = true">
            @csrf

            <div>
                {{-- x-cloak: without JS the boxes never appear and the plain
                     field below is what the user types into. --}}
                <div x-cloak class="flex items-center justify-between gap-2" @paste="onPaste($event)">
                    @for ($i = 0; $i < 6; $i++)
                        <input type="text" inputmode="numeric" maxlength="1"
                            x-ref="box{{ $i }}"
                            @if ($i === 0) autofocus autocomplete="one-time-code" @endif
                            @input="onInput({{ $i }}, $event)"
                            @keydown="onKeydown({{ $i }}, $event)"
                            @focus="$event.target.select()"
                            aria-label="Digit {{ $i + 1 }} of 6"
                            class="w-full aspect-square min-w-0 bg-warm rounded text-center text-xl font-medium text-ink
                                border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                    @endfor
                </div>

                {{--
                    The field that actually posts.

                    Alpine flips it to type=hidden, so with JS it silently carries
                    whatever the boxes hold. Without JS it stays a plain visible
                    text input and verification works exactly as it did before.

                    Flipping the TYPE rather than hiding with a class matters:
                    browsers skip constraint validation on hidden inputs, whereas
                    a `required` field hidden by display:none or sr-only blocks
                    submission with an error it cannot show, because it cannot be
                    focused to explain why.

                    :value not x-model — `code` is a getter with no setter.
                --}}
                <input type="text" name="code" inputmode="numeric" maxlength="6" required
                    :type="'hidden'" :value="code"
                    class="w-full bg-warm rounded text-center text-xl tracking-[0.5em] font-medium text-ink px-4 py-3 mt-3
                        border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors"
                    placeholder="••••••" aria-label="6-digit verification code">

                @error('code')
                    <p class="mt-3 text-sm text-accent text-center">{{ $message }}</p>
                @enderror
            </div>

            <x-submit-button loadingText="Verifying…"
                class="w-full bg-ink text-white rounded-full px-4 py-3 text-sm font-medium hover:bg-ink/90">
                Verify email
            </x-submit-button>
        </form>

        <div class="flex items-center justify-between mt-6 text-sm">
            {{-- Resend cooldown. The endpoint is rate limited server-side too;
                 this stops someone burning that budget by reflex-clicking, and
                 tells them how long they have to wait instead of failing. --}}
            <form method="POST" action="{{ route('verification.send') }}"
                x-data="{
                    left: 0,
                    timer: null,
                    start() {
                        this.left = 30;
                        this.timer = setInterval(() => {
                            if (--this.left <= 0) clearInterval(this.timer);
                        }, 1000);
                    },
                }"
                @submit="start()">
                @csrf
                <button type="submit" :disabled="left > 0"
                    class="text-muted hover:text-ink transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:text-muted"
                    x-text="left > 0 ? `Resend in ${left}s` : 'Resend code'">Resend code</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-muted hover:text-ink transition-colors">Log out</button>
            </form>
        </div>
    </div>
@endsection
