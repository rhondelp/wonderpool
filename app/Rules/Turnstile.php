<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cloudflare Turnstile check (D-028). Always passes while config('wonderpool.turnstile.enabled') is false.
 * When enabled, the form must post the widget's "cf-turnstile-response" token, verified server-side.
 */
class Turnstile implements ValidationRule
{
    /** Run even when the token field is missing (Laravel skips non-implicit rules for absent fields). */
    public bool $implicit = true;

    /** Cloudflare verification endpoint. */
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * @param  string|null  $ip  Client IP passed to Cloudflare (optional)
     */
    public function __construct(private readonly ?string $ip = null)
    {
    }

    /**
     * Whether the Turnstile check is switched on.
     */
    public static function enabled(): bool
    {
        return (bool) config('wonderpool.turnstile.enabled');
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::enabled()) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Please complete the security check.');

            return;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, array_filter([
                'secret' => (string) config('wonderpool.turnstile.secret_key'),
                'response' => $value,
                'remoteip' => $this->ip,
            ]));
            $ok = $response->successful() && $response->json('success') === true;
        } catch (Throwable) {
            $ok = false;
        }

        if (! $ok) {
            $fail('The security check failed. Please try again.');
        }
    }
}
