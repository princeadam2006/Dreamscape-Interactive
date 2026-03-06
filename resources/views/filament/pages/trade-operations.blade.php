<x-filament-panels::page>
    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Trading</p>
        <h2 class="mt-2 text-2xl font-bold text-gray-900">Trade Operations</h2>
        <p class="mt-2 text-sm text-gray-600">Keep negotiations moving and prevent stalled deals from blocking your inventory.</p>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->getTradeStats() as $stat)
                <div class="rounded-2xl border border-gray-200 p-4">
                    <p class="text-xs uppercase tracking-wider text-gray-500">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-5">
        <div class="xl:col-span-2 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900">Quick Actions</h3>
            <div class="mt-4 space-y-3">
                @foreach ($this->getQuickLinks() as $quickLink)
                    <a
                        href="{{ $quickLink['url'] }}"
                        class="block rounded-xl border border-gray-200 p-3 transition hover:border-cyan-300 hover:bg-cyan-50/40"
                    >
                        <p class="text-sm font-semibold text-gray-900">{{ $quickLink['label'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $quickLink['description'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="xl:col-span-3 grid gap-6">
            <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">Incoming Open Trades</h3>
                <div class="mt-4 space-y-2">
                    @forelse ($this->getIncomingTrades() as $trade)
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3">
                            <p class="text-sm font-semibold text-gray-900">
                                #{{ $trade->id }} from {{ $trade->initiator?->username ?? 'Unknown' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-600">Expires {{ $trade->expires_at?->diffForHumans() ?? 'soon' }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No incoming open trades.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">Outgoing Open Trades</h3>
                <div class="mt-4 space-y-2">
                    @forelse ($this->getOutgoingTrades() as $trade)
                        <div class="rounded-xl border border-cyan-200 bg-cyan-50/50 p-3">
                            <p class="text-sm font-semibold text-gray-900">
                                #{{ $trade->id }} to {{ $trade->receiver?->username ?? 'Unknown' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-600">Expires {{ $trade->expires_at?->diffForHumans() ?? 'soon' }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No outgoing open trades.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</x-filament-panels::page>
