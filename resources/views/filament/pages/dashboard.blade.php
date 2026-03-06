<x-filament-panels::page>
    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Dreamscape Interactive</p>
        <h2 class="mt-2 text-2xl font-bold text-gray-900">Operational Hub</h2>
        <p class="mt-2 max-w-2xl text-sm text-gray-600">
            Quick actions and system visibility are grouped here so day-to-day workflows stay fast.
        </p>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->getQuickLinks() as $quickLink)
                <a
                    href="{{ $quickLink['url'] }}"
                    class="group rounded-2xl border border-gray-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-cyan-300 hover:shadow-md"
                >
                    <p class="text-sm font-semibold text-gray-900">{{ $quickLink['label'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $quickLink['description'] }}</p>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wider text-cyan-700 group-hover:text-cyan-900">Open</p>
                </a>
            @endforeach
        </div>
    </section>

    <div class="mt-6">
        {{ $this->content }}
    </div>
</x-filament-panels::page>
