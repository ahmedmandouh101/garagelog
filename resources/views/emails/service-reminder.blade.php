@component('mail::message')
#  Service Reminder — {{ $car->make }} {{ $car->model }}

Hi **{{ $owner->name }}**,

Your car is due for a service in **7 days**. Don't wait until it's too late!

---

@component('mail::panel')
**Car Details**

- **Make & Model:** {{ $car->make }} {{ $car->model }} ({{ $car->year }})
- **Plate Number:** {{ $car->plate_number }}
- **Current Mileage:** {{ number_format($car->mileage) }} km
@endcomponent

@if($latestService)
@component('mail::panel')
**Last Service**

- **Type:** {{ $latestService->service_type }}
- **Date:** {{ $latestService->service_date->format('d M Y') }}
- **Garage:** {{ $latestService->garage->name ?? 'N/A' }}
- **Next Service Due:** {{ $latestService->next_service_date->format('d M Y') }}
@endcomponent
@endif

@component('mail::button', ['url' => config('app.url'), 'color' => 'blue'])
View Car History
@endcomponent

Stay safe on the road! 

Thanks,
**{{ config('app.name') }}**

@endcomponent
