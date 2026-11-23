@props(['status'])

<span @class([
    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
    'bg-amber-100 text-amber-900' => $status === \App\Models\BookingStatus::Pending,
    'bg-emerald-100 text-emerald-900' => $status === \App\Models\BookingStatus::Accepted,
    'bg-red-100 text-red-900' => $status === \App\Models\BookingStatus::Rejected,
    'bg-stone-100 text-stone-800' => $status === \App\Models\BookingStatus::Cancelled,
    'bg-sky-100 text-sky-900' => $status === \App\Models\BookingStatus::Completed,
])>{{ ucfirst($status->value) }}</span>
