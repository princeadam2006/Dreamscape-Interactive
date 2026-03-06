<x-filament-panels::page>
    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Administration</p>
        <h2 class="mt-2 text-2xl font-bold text-gray-900">Control Center</h2>
        <p class="mt-2 text-sm text-gray-600">Centralize operational insight, risk monitoring, and moderation actions.</p>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->getOverviewStats() as $stat)
                <div class="rounded-2xl border border-gray-200 p-4">
                    <p class="text-xs uppercase tracking-wider text-gray-500">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-5">
        <div class="xl:col-span-2 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900">Quick Links</h3>
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

        <div class="xl:col-span-3 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900">Expiring Soon (6h)</h3>
            <div class="mt-4 space-y-3">
                @forelse ($this->getExpiringTrades() as $trade)
                    <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3">
                        <p class="text-sm font-semibold text-gray-900">
                            Trade #{{ $trade->id }}:
                            {{ $trade->initiator?->username ?? 'Unknown' }} -> {{ $trade->receiver?->username ?? 'Unknown' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-600">Expires {{ $trade->expires_at?->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No trades are nearing expiration.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="text-base font-semibold text-gray-900">Recent Audit Events</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-2 py-2">Time</th>
                        <th class="px-2 py-2">Action</th>
                        <th class="px-2 py-2">Actor</th>
                        <th class="px-2 py-2">Target</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getRecentAuditLogs() as $auditLog)
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="px-2 py-2 text-gray-600">{{ $auditLog->created_at?->diffForHumans() }}</td>
                            <td class="px-2 py-2 font-medium text-gray-900">{{ $auditLog->action }}</td>
                            <td class="px-2 py-2 text-gray-600">{{ $auditLog->user?->username ?? 'System' }}</td>
                            <td class="px-2 py-2 text-gray-600">{{ $auditLog->targetUser?->username ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-2 py-3 text-sm text-gray-500">No audit logs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-filament-panels::page>
