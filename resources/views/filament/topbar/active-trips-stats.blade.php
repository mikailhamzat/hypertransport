<span class="text-sm">
    <x-heroicon-m-truck class="inline-block mr-1 align-middle" style="width: 20px !important; height: 20px !important; display: inline-block !important;" />
    <b>{{ number_format($activeTripsCount) }}</b> {{ Str::plural('Active Trip', $activeTripsCount) }}
</span>