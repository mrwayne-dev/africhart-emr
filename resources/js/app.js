import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

/*
 * Toast store — a global, append-only list of notifications.
 * Anything can call window.toast('success', 'Saved!') or dispatch a
 * `toast` window event with { type, message } detail.
 */
Alpine.store('toasts', {
    items: [],
    nextId: 1,

    push(type, message) {
        const id = this.nextId++;
        this.items.push({ id, type, message });
        // Auto-dismiss after 4s
        setTimeout(() => this.remove(id), 4000);
    },

    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

window.toast = (type, message) => Alpine.store('toasts').push(type, message);

window.addEventListener('toast', (e) => {
    window.toast(e.detail?.type ?? 'success', e.detail?.message ?? '');
});

/*
 * Patient create/edit modal — submits via fetch so validation errors show
 * inline without leaving the page. On success it follows the server redirect
 * (which carries a flashed success message that surfaces as a toast).
 */
Alpine.data('patientModal', () => ({
    open: false,
    mode: 'create',
    processing: false,
    id: null,
    idempotencyKey: null,
    form: { full_name: '', date_of_birth: '', phone: '', blood_group: '', allergies: '' },
    errors: {},

    reset() {
        this.errors = {};
        this.id = null;
        this.idempotencyKey = null;
        this.form = { full_name: '', date_of_birth: '', phone: '', blood_group: '', allergies: '' };
    },

    openCreate() {
        this.reset();
        this.mode = 'create';
        // One key per registration attempt — survives retries so a double-submit
        // after a flaky response can't create two patient records.
        this.idempotencyKey = (crypto.randomUUID?.() ?? String(Date.now() + Math.random()));
        this.open = true;
    },

    openEdit(patient) {
        this.reset();
        this.mode = 'edit';
        this.id = patient.id;
        this.form = {
            full_name: patient.full_name ?? '',
            date_of_birth: patient.date_of_birth ?? '',
            phone: patient.phone ?? '',
            blood_group: patient.blood_group ?? '',
            allergies: patient.allergies ?? '',
        };
        this.open = true;
    },

    close() {
        this.open = false;
    },

    error(field) {
        return this.errors[field]?.[0] ?? null;
    },

    async submit() {
        if (this.processing) return;
        this.processing = true;
        this.errors = {};

        const url = this.mode === 'create' ? '/patients' : `/patients/${this.id}`;
        const method = this.mode === 'create' ? 'POST' : 'PUT';

        const headers = {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        };
        if (this.mode === 'create' && this.idempotencyKey) {
            headers['X-Idempotency-Key'] = this.idempotencyKey;
        }

        try {
            const res = await fetch(url, { method, headers, body: JSON.stringify(this.form) });

            if (res.status === 422) {
                const data = await res.json();
                this.errors = data.errors ?? {};
                this.processing = false;
                return;
            }

            // Expired CSRF / session — the submit can't succeed until the page reloads.
            if (res.status === 419) {
                window.toast('error', 'Your session expired — refreshing the page…');
                setTimeout(() => window.location.reload(), 1200);
                return;
            }

            if (!res.ok) throw new Error('Request failed');

            const data = await res.json();
            window.location = data.redirect;
        } catch (e) {
            // fetch only throws on a network-level failure (offline, timeout, DNS).
            this.processing = false;
            const noun = this.mode === 'create' ? 'patient was NOT saved' : 'changes were NOT saved';
            window.toast('error', `Network problem — ${noun}. Please check your connection and try again.`);
        }
    },
}));

/*
 * Queue vitals modal — the nurse records vitals against a waiting queue entry,
 * before any consultation is open. One modal serves every row; a row button
 * dispatches `open-queue-vitals` with the entry's id + current vitals. Submits
 * via fetch so validation errors show inline; on success follows the redirect.
 */
Alpine.data('queueVitals', () => ({
    open: false,
    processing: false,
    id: null,
    patient: '',
    form: { temperature: '', blood_pressure: '', pulse_rate: '', weight: '', height: '', vitals_notes: '' },
    errors: {},

    openFor(detail) {
        this.errors = {};
        this.id = detail.id;
        this.patient = detail.patient ?? '';
        this.form = {
            temperature: detail.temperature ?? '',
            blood_pressure: detail.blood_pressure ?? '',
            pulse_rate: detail.pulse_rate ?? '',
            weight: detail.weight ?? '',
            height: detail.height ?? '',
            vitals_notes: detail.vitals_notes ?? '',
        };
        this.open = true;
    },

    close() {
        this.open = false;
    },

    error(field) {
        return this.errors[field]?.[0] ?? null;
    },

    get bmi() {
        const w = parseFloat(this.form.weight);
        const h = parseFloat(this.form.height);
        if (!w || !h) return null;
        const m = h / 100;
        return (w / (m * m)).toFixed(1);
    },

    async submit() {
        if (this.processing) return;
        this.processing = true;
        this.errors = {};

        try {
            const res = await fetch(`/queue/${this.id}/vitals`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(this.form),
            });

            if (res.status === 422) {
                this.errors = (await res.json()).errors ?? {};
                this.processing = false;
                return;
            }
            if (res.status === 419) {
                window.toast('error', 'Your session expired — refreshing…');
                setTimeout(() => window.location.reload(), 1200);
                return;
            }
            if (!res.ok) throw new Error('Request failed');

            window.location = (await res.json()).redirect;
        } catch (e) {
            this.processing = false;
            window.toast('error', 'Network problem — vitals were NOT saved. Please try again.');
        }
    },
}));

/*
 * Prescription form — a repeatable set of medication rows the doctor can grow
 * or shrink before submitting them all at once (normal POST, items[] array).
 * `presets` is the common-medication list used for lightweight autocomplete.
 */
Alpine.data('prescriptionForm', (presets = []) => ({
    loading: false,
    presets,
    blank() {
        return { medication_name: '', dosage: '', frequency: '', duration: '', route: 'oral', instructions: '', quantity: '' };
    },
    items: [],

    init() {
        this.items = [this.blank()];
    },

    addItem() {
        this.items.push(this.blank());
    },

    removeItem(index) {
        if (this.items.length > 1) this.items.splice(index, 1);
    },

    // Fill dosage/frequency/route from a preset when the medication name matches.
    applyPreset(index) {
        const name = (this.items[index].medication_name || '').trim().toLowerCase();
        const preset = this.presets.find((p) => p.name.toLowerCase() === name);
        if (!preset) return;
        if (!this.items[index].dosage && preset.dosages?.length) this.items[index].dosage = preset.dosages[0];
        if (!this.items[index].frequency && preset.common_frequency) this.items[index].frequency = preset.common_frequency;
        if (preset.routes?.length) this.items[index].route = preset.routes[0];
    },
}));

/*
 * livePoll — near-real-time updates without WebSockets. Polls a "live" endpoint
 * that returns { hash, html, meta }; swaps the region's HTML only when the hash
 * changes (re-initialising Alpine inside it), pauses while the tab is hidden, and
 * won't yank a form control the user is currently interacting with.
 *
 * Usage:  <div x-data="livePoll({ url: '/queue/live', interval: 8000, label: 'Queue updated',
 *                                  hash: '{{ $liveHash }}', meta: { count: {{ $queue->count() }} } })">
 *           <span x-text="meta.count"></span>
 *           <div x-ref="region"> ...server-rendered partial... </div>
 *         </div>
 */
Alpine.data('livePoll', (config = {}) => ({
    url: config.url,
    interval: config.interval ?? 8000,
    label: config.label ?? null,
    mode: config.mode ?? 'swap', // 'swap' = replace region HTML; 'notify' = show a refresh banner
    hash: config.hash ?? null,
    meta: config.meta ?? {},
    stale: false, // notify mode: set true when the server data has changed
    inflight: false,
    timer: null,

    init() {
        this.timer = setInterval(() => this.tick(), this.interval);
    },

    destroy() {
        if (this.timer) clearInterval(this.timer);
    },

    async tick() {
        if (document.hidden || this.inflight || !this.url || this.stale) return;
        this.inflight = true;
        try {
            const res = await fetch(this.url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.hash === this.hash) return;

            // Notify mode: don't touch the DOM (the page may hold unsaved input) — flag it.
            if (this.mode === 'notify') {
                this.stale = true;
                return;
            }

            // Focus guard: don't replace the region while the user is using a control in it.
            const region = this.$refs.region;
            const active = document.activeElement;
            if (region && active && region.contains(active) && /^(INPUT|SELECT|TEXTAREA)$/.test(active.tagName)) {
                return; // retry on the next tick
            }

            this.hash = data.hash;
            this.meta = data.meta ?? this.meta;
            if (region && data.html != null) {
                region.innerHTML = data.html;
                window.Alpine.initTree(region);
            }
            if (this.label) window.toast('success', this.label);
        } catch (e) {
            // transient network error — ignore and try next tick
        } finally {
            this.inflight = false;
        }
    },
}));

Alpine.start();
