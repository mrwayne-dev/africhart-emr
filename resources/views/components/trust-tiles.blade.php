@props(['tiles'])

{{--
    Capability tile row, structure from the "Compliant by design" reference.

    IMPORTANT — the reference is a wall of certification badges (GDPR, HIPAA,
    ISO 27001, SOC 2). AfriChart holds none of those, so every tile here states
    a capability we can actually demonstrate. Do not swap these for certification
    marks unless and until the certificate exists: this is read by clinic owners
    deciding whether to trust us with patient records.

    The graph-paper texture is drawn in CSS from --color-line, so there's no
    image asset to ship and it recolours automatically with the token.
--}}
<div class="rounded-card border border-line overflow-hidden bg-page"
    style="
        background-image:
            linear-gradient(to right, var(--color-line) 1px, transparent 1px),
            linear-gradient(to bottom, var(--color-line) 1px, transparent 1px);
        background-size: 32px 32px;
    ">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 p-6 sm:p-8">
        @foreach ($tiles as $i => $tile)
            <div class="bg-page border border-line rounded-card overflow-hidden flex flex-col
                    transition-all duration-200 hover:-translate-y-0.5 hover:border-muted/30"
                data-reveal data-reveal-delay="{{ $i * 60 }}">

                <div class="flex items-center justify-center py-7 px-4">
                    <x-dynamic-component :component="$tile['icon']" class="w-9 h-9 text-ink" aria-hidden="true" />
                </div>

                <div class="bg-warm border-t border-line px-3 py-3 text-center mt-auto">
                    <p class="text-xs font-semibold text-ink uppercase tracking-wide leading-tight">{{ $tile['name'] }}</p>
                    <p class="text-[10px] text-muted uppercase tracking-wide mt-1">{{ $tile['sub'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
