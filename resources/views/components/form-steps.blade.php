@props([
    'action',
    'steps' => [],              // [['label' => …, 'fields' => ['name', …]], …]
    'submitLabel' => 'Submit',
    'loadingText' => 'Sending…',
])

@php
    /*
     * Which step do we open on?
     *
     * Normally the first. But after a failed submit the visitor must land back
     * on the step that actually failed, with everything they typed still there
     * — being thrown back to an empty step 1 is the way multi-step forms lose
     * people, and it is the single thing most worth getting right here.
     *
     * Every field is repopulated from old() by the field component already, so
     * only the step index has to be recovered. It is derived from the validation
     * errors rather than round-tripped through a hidden input, so it cannot be
     * spoofed and cannot go stale.
     */
    $openStep = 1;

    foreach ($steps as $i => $step) {
        foreach ($step['fields'] as $field) {
            if ($errors->has($field)) {
                $openStep = $i + 1;
                break 2;
            }
        }
    }

    $total = count($steps);
@endphp

{{--
    Multi-step form shell, shared by Book a Demo (2 steps) and Get Started (3).

    All panels stay in the DOM and are toggled with x-show, so every field posts
    regardless of which step is on screen — there is no per-step draft to keep in
    sync, and the server receives one ordinary request.

    Advancing runs the browser's own validation over that panel's controls. That
    is not only UX: a hidden REQUIRED control that is invalid silently blocks
    form submission in every browser and cannot be focused to explain why. Gating
    the step on reportValidity() guarantees hidden fields are always valid ones.

    Without JS, Alpine never runs, x-show never applies, and all panels render as
    one ordinary long form that submits correctly. That is the intended fallback,
    and it is why the panels are not x-cloaked.
--}}
<form method="POST" action="{{ $action }}"
    x-data="{
        step: {{ $openStep }},
        total: {{ $total }},
        labels: @js(array_column($steps, 'label')),
        loading: false,

        get label() { return this.labels[this.step - 1] },

        next() {
            const panel = this.$refs['panel' + this.step];

            // Native constraint validation over this panel only. reportValidity
            // also shows the browser's own message on the first bad control.
            const controls = panel ? panel.querySelectorAll('input, select, textarea') : [];
            for (const control of controls) {
                if (! control.checkValidity()) { control.reportValidity(); return }
            }

            if (this.step < this.total) this.step++;
        },

        back() { if (this.step > 1) this.step-- },
    }"
    @submit="loading = true"
    {{ $attributes }}>
    @csrf

    {{-- Progress. Hidden without JS, where the form is a single list and a
         'Step 1 of N' label would be a lie. --}}
    <div x-cloak class="mb-6">
        <div class="flex items-center justify-between gap-4">
            <p class="text-xs font-semibold text-muted uppercase tracking-wide">
                Step <span x-text="step">1</span> of {{ $total }}
            </p>
            <p class="text-sm font-medium text-ink" x-text="label"></p>
        </div>

        {{-- One rule, filled to the current step. A row of numbered circles would
             be four more elements saying the same thing. --}}
        <div class="flex items-center gap-1.5 mt-3" aria-hidden="true">
            @for ($i = 1; $i <= $total; $i++)
                <div class="h-0.5 flex-1 rounded-full overflow-hidden bg-line">
                    <div class="h-full bg-ink origin-left transition-transform duration-500 ease-out"
                        :class="step >= {{ $i }} ? 'scale-x-100' : 'scale-x-0'"></div>
                </div>
            @endfor
        </div>

        <span class="sr-only" aria-live="polite"
            x-text="'Step ' + step + ' of ' + total"></span>
    </div>

    {{ $slot }}

    <div class="flex items-center gap-3 mt-6">
        <button type="button" x-cloak x-show="step > 1" @click="back()"
            class="inline-flex items-center gap-1.5 border border-line text-ink rounded-full px-5 py-3.5
                text-sm font-medium hover:bg-warm hover:border-muted/30 transition-colors">
            <x-phosphor-arrow-left class="w-4 h-4" aria-hidden="true" />
            Back
        </button>

        {{-- Continue and Submit swap on the last step. Both are rendered so the
             no-JS fallback still has a working submit button. --}}
        <button type="button" x-cloak x-show="step < total" @click="next()"
            class="group flex-1 inline-flex items-center justify-center gap-1.5 bg-ink text-white rounded-full
                px-6 py-3.5 text-sm font-medium hover:bg-ink/90 transition-colors">
            Continue
            <x-phosphor-arrow-right class="w-4 h-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
        </button>

        <div class="flex-1" x-show="step === total">
            <x-submit-button :loadingText="$loadingText"
                class="w-full bg-ink text-white rounded-full px-6 py-3.5 text-sm font-medium hover:bg-ink/90">
                {{ $submitLabel }}
            </x-submit-button>
        </div>
    </div>
</form>
