{{--
    Queue vitals modal — opened from a queue row with:
        $dispatch('open-queue-vitals', { id, patient, temperature, ... })
    Lives outside the live-polled region so it survives table swaps.
--}}
<div x-data="queueVitals()" x-on:open-queue-vitals.window="openFor($event.detail)">
    <div x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/40" @click="close()"></div>

        <div x-show="open" x-transition
            class="relative bg-page rounded-card border border-line w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line sticky top-0 bg-page">
                <h2 class="text-base font-medium text-ink tracking-tight">
                    Record Vitals <span class="text-muted font-normal" x-text="patient ? '· ' + patient : ''"></span>
                </h2>
                <button type="button" @click="close()" class="text-muted hover:text-ink">
                    <x-phosphor-x class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submit()" class="p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-2">Temperature (°C)</label>
                        <input type="number" step="0.1" x-model="form.temperature" placeholder="37.0"
                            class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                        <p x-show="error('temperature')" x-text="error('temperature')" class="mt-1 text-xs text-accent"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-2">Blood Pressure</label>
                        <input type="text" x-model="form.blood_pressure" placeholder="120/80"
                            class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                        <p x-show="error('blood_pressure')" x-text="error('blood_pressure')" class="mt-1 text-xs text-accent"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-2">Pulse (bpm)</label>
                        <input type="number" x-model="form.pulse_rate" placeholder="72"
                            class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                        <p x-show="error('pulse_rate')" x-text="error('pulse_rate')" class="mt-1 text-xs text-accent"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-2">Weight (kg)</label>
                        <input type="number" step="0.1" x-model="form.weight" placeholder="68.0"
                            class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                        <p x-show="error('weight')" x-text="error('weight')" class="mt-1 text-xs text-accent"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-body mb-2">Height (cm)</label>
                        <input type="number" step="0.1" x-model="form.height" placeholder="170.0"
                            class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors">
                        <p x-show="error('height')" x-text="error('height')" class="mt-1 text-xs text-accent"></p>
                    </div>
                    <div class="flex items-end">
                        <p class="text-sm text-muted" x-show="bmi" x-cloak>
                            BMI <span class="font-medium text-ink" x-text="bmi"></span>
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink-body mb-2">
                        Notes <span class="text-muted font-normal">(optional)</span>
                    </label>
                    <textarea x-model="form.vitals_notes" rows="2"
                        class="w-full bg-warm rounded text-sm text-ink-body px-3 py-2.5 border border-transparent focus:bg-page focus:border-ink focus:outline-none transition-colors"></textarea>
                    <p x-show="error('vitals_notes')" x-text="error('vitals_notes')" class="mt-1 text-xs text-accent"></p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="close()" class="text-sm text-muted hover:text-ink transition-colors px-2">
                        Cancel
                    </button>
                    <button type="submit" :disabled="processing"
                        class="inline-flex items-center justify-center gap-2 bg-ink text-white rounded-full px-6 py-3 text-sm font-medium hover:bg-ink/90 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <x-spinner x-show="processing" x-cloak class="w-4 h-4" />
                        <span x-text="processing ? 'Saving…' : 'Save Vitals'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
