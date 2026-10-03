@isset($brand)
{{ $brand['name'] }}
{{ $brand['address'] }}
{{ $brand['phone'] }} · {{ $brand['email'] }}
@endisset
{{ $slot }}
