{{-- Mail footer (M8): resort contact details from Settings ($brand via MailBrandComposer), then the slot. --}}
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
@isset($brand)
<p><strong>{{ $brand['name'] }}</strong><br>
{{ $brand['address'] }}<br>
{{ $brand['phone'] }} · <a href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a>
@if ($brand['facebook_url'])<br><a href="{{ $brand['facebook_url'] }}">Facebook</a>@endif
</p>
@endisset
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>
