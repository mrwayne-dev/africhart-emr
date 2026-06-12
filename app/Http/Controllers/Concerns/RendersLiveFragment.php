<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Helper for "live" polling endpoints: render a Blade partial to a string and
 * pair it with a cheap change-hash. The client only swaps the DOM when the hash
 * changes, so styling/role-gating stay in Blade and there's no client templating.
 */
trait RendersLiveFragment
{
    /**
     * @param  array<string, mixed>  $data  view data for the partial
     * @param  array<mixed>  $hashParts  cheap change signals (ids, statuses, timestamps…)
     * @param  array<string, mixed>  $meta  small extras bound reactively in the page (e.g. count)
     */
    protected function liveFragment(string $view, array $data, array $hashParts, array $meta = []): JsonResponse
    {
        return response()->json([
            'hash' => $this->liveHash($hashParts),
            'html' => view($view, $data)->render(),
            'meta' => $meta,
        ]);
    }

    /**
     * Hash-only response for "notify" mode (no HTML — the page shows a refresh banner).
     *
     * @param  array<mixed>  $hashParts
     */
    protected function liveHashResponse(array $hashParts): JsonResponse
    {
        return response()->json(['hash' => $this->liveHash($hashParts)]);
    }

    protected function liveHash(array $parts): string
    {
        return md5(json_encode($parts));
    }
}
