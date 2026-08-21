{{--
    Two side-by-side CTA cards. The reference uses green + yellow; we use the
    ink card as the primary and a bordered warm card as the secondary, which
    keeps the same visual weight relationship without leaving our palette.
--}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    <a href="{{ route('signup') }}"
        class="group bg-ink rounded-card p-8 flex flex-col justify-between min-h-[220px] transition-colors hover:bg-ink-body">
        <div class="flex items-start gap-3">
            <span class="bg-page/10 rounded-card p-2 shrink-0">
                <x-phosphor-first-aid-kit class="w-5 h-5 text-white" aria-hidden="true" />
            </span>
            <p class="text-sm font-medium text-white/80 leading-relaxed">
                Start running your clinic on AfriChart
            </p>
        </div>
        <div class="flex items-end justify-between gap-4 mt-10">
            <span class="text-2xl sm:text-3xl font-medium text-white tracking-tight">Get started</span>
            <x-phosphor-arrow-right class="w-6 h-6 text-white shrink-0 transition-transform group-hover:translate-x-1" aria-hidden="true" />
        </div>
    </a>

    <a href="{{ route('demo') }}"
        class="group bg-page border border-line rounded-card p-8 flex flex-col justify-between min-h-[220px] transition-colors hover:bg-warm">
        <div class="flex items-start gap-3">
            <span class="bg-warm rounded-card p-2 shrink-0">
                <x-phosphor-whatsapp-logo class="w-5 h-5 text-ink" aria-hidden="true" />
            </span>
            <p class="text-sm font-medium text-muted leading-relaxed">
                See it working on your own clinic's workflow
            </p>
        </div>
        <div class="flex items-end justify-between gap-4 mt-10">
            <span class="text-2xl sm:text-3xl font-medium text-ink tracking-tight">Book a demo</span>
            <x-phosphor-arrow-right class="w-6 h-6 text-ink shrink-0 transition-transform group-hover:translate-x-1" aria-hidden="true" />
        </div>
    </a>
</div>
