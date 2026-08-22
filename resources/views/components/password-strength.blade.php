@props(['for'])

{{--
    Password strength meter, bound to a password input by id.

    It scores what the field actually enforces plus the things that genuinely
    make a password harder to guess — length above all. It is guidance, never a
    gate: the server rules decide what is accepted, and a meter that blocks
    submission on its own opinion would reject passwords the backend allows.

    Length is weighted twice because it is worth more than character variety,
    and a meter that rewards "P@ss1!" over "correct horse battery staple" is
    teaching the wrong lesson.
--}}
<div class="mt-3"
    x-data="{
        score: 0,

        get label() {
            return ['Too short', 'Weak', 'Fair', 'Good', 'Strong'][this.score] ?? '';
        },

        measure(value) {
            if (! value) { this.score = 0; return }

            let points = 0;
            if (value.length >= 8) points++;
            if (value.length >= 12) points++;          // length counts twice
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) points++;
            if (/[0-9]/.test(value) || /[^A-Za-z0-9]/.test(value)) points++;

            this.score = Math.min(points, 4);
        },

        init() {
            const input = document.getElementById(@js($for));
            if (! input) return;

            this.measure(input.value);
            input.addEventListener('input', () => this.measure(input.value));
        },
    }"
    x-cloak>

    <div class="flex items-center gap-1.5" aria-hidden="true">
        @for ($i = 1; $i <= 4; $i++)
            <div class="h-1 flex-1 rounded-full overflow-hidden bg-line">
                <div class="h-full origin-left transition-transform duration-300 ease-out"
                    :class="[
                        score >= {{ $i }} ? 'scale-x-100' : 'scale-x-0',
                        score <= 2 ? 'bg-accent' : 'bg-ink',
                    ]"></div>
            </div>
        @endfor
    </div>

    {{-- Polite, so it does not interrupt on every keystroke. --}}
    <p class="text-xs text-muted mt-2" aria-live="polite" x-text="label"></p>
</div>
