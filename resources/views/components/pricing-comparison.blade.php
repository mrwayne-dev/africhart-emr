@props(['groups', 'tiers'])

{{--
    Plan comparison.

    Uses the app's existing table idiom — overflow-x-auto wrapper, `w-full
    text-sm`, hairline row borders — rather than inventing a marketing-only
    table. Four columns need horizontal room; on a phone it scrolls, which is
    how every table in the EMR already behaves.

    The header row sticks while you scroll a long table, so you never lose
    which column is which.
--}}
<div class="border border-line rounded-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[46rem]">
            <caption class="sr-only">Feature comparison across the Starter, Clinic and Group plans</caption>

            <thead>
                <tr class="bg-page">
                    <th scope="col" class="text-left px-6 py-5 font-medium text-muted w-2/5">Plan</th>
                    @foreach (['starter' => 'Starter', 'clinic' => 'Clinic', 'group' => 'Group'] as $key => $name)
                        <th scope="col" @class([
                            'text-left px-6 py-5 font-medium text-ink',
                            'bg-warm' => $key === 'clinic',
                        ])>
                            {{ $name }}
                            @if ($key === 'clinic')
                                <span class="block text-xs font-normal text-muted mt-0.5">Most popular</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>

            @foreach ($groups as $groupName => $rows)
                <tbody>
                    <tr>
                        <th colspan="4" scope="colgroup"
                            class="text-left px-6 pt-8 pb-3 text-xs font-semibold text-muted uppercase tracking-wide border-t border-line">
                            {{ $groupName }}
                        </th>
                    </tr>

                    @foreach ($rows as $row)
                        <tr class="border-t border-line">
                            <th scope="row" class="text-left px-6 py-3.5 font-normal text-ink-body">{{ $row['label'] }}</th>

                            @foreach (['starter', 'clinic', 'group'] as $key)
                                <td @class(['px-6 py-3.5', 'bg-warm' => $key === 'clinic'])>
                                    @if ($row[$key] === true)
                                        <x-phosphor-check class="w-4 h-4 text-ink" aria-hidden="true" />
                                        <span class="sr-only">Included</span>
                                    @elseif ($row[$key] === false)
                                        <span class="block w-3 h-px bg-line" aria-hidden="true"></span>
                                        <span class="sr-only">Not included</span>
                                    @else
                                        <span class="text-ink-body">{{ $row[$key] }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            @endforeach

            <tfoot>
                <tr class="border-t border-line">
                    <td class="px-6 py-6"></td>
                    @foreach ($tiers as $tier)
                        @php $isClinic = $tier['name'] === 'Clinic'; @endphp
                        <td @class(['px-6 py-6 align-top', 'bg-warm' => $isClinic])>
                            <a href="{{ $tier['cta'] === 'Talk to us' ? route('demo') : route('signup') }}"
                                @class([
                                    'inline-flex items-center justify-center gap-1.5 w-full rounded-full px-4 py-2.5 text-sm font-medium transition-colors',
                                    'bg-ink text-white hover:bg-ink/90' => $isClinic,
                                    'border border-line text-ink hover:bg-warm hover:border-muted/30' => ! $isClinic,
                                ])>
                                {{ $tier['cta'] }}
                            </a>
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
</div>
