{{-- Mail header (M8): logo from Settings → General (general.logo_url) or the resort name. $brand comes from MailBrandComposer. --}}
@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (! empty($brand['logo_url']))
<img src="{{ $brand['logo_url'] }}" class="logo" alt="{{ $brand['name'] }}">
@else
{{ $brand['name'] ?? $slot }}
@endif
</a>
</td>
</tr>
