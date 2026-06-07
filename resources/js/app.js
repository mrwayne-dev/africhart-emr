import Alpine from 'alpinejs';

window.Alpine = Alpine;

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
    form: { full_name: '', date_of_birth: '', phone: '', blood_group: '', allergies: '' },
    errors: {},

    reset() {
        this.errors = {};
        this.id = null;
        this.form = { full_name: '', date_of_birth: '', phone: '', blood_group: '', allergies: '' };
    },

    openCreate() {
        this.reset();
        this.mode = 'create';
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

        try {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(this.form),
            });

            if (res.status === 422) {
                const data = await res.json();
                this.errors = data.errors ?? {};
                this.processing = false;
                return;
            }

            if (!res.ok) throw new Error('Request failed');

            const data = await res.json();
            window.location = data.redirect;
        } catch (e) {
            this.processing = false;
            window.toast('error', 'Something went wrong. Please try again.');
        }
    },
}));

Alpine.start();
