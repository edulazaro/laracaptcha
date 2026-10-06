<?php

namespace EduLazaro\Laracaptcha\Concerns;

use EduLazaro\Laracaptcha\Support\VerificationResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * The one HTTP call every driver makes, and the failure modes around it.
 *
 * Both providers expose the same shape of endpoint, a form post carrying the
 * secret and the token, so the interesting part here is not the request but
 * what happens when it does not come back.
 */
trait TalksToProvider
{
    /**
     * Post the token to the provider and hand back its decoded payload.
     *
     * Returns null when the provider could not be reached at all: a refused
     * connection, a DNS failure, or a request that ran past the timeout. The
     * caller turns that into a failed verification, because a captcha that
     * could not be checked is not a captcha that passed, and because letting
     * the exception out would turn a provider outage into a 500 on whatever
     * form the widget sits on.
     *
     * A reply that arrives but is not JSON, an error page for instance, is not
     * a connection failure: it decodes to an empty payload, which carries no
     * "success" key and therefore fails too.
     *
     * @param string $endpoint
     * @param string $token
     * @param string|null $ip
     * @return array<string, mixed>|null
     */
    protected function askProvider(string $endpoint, string $token, ?string $ip): ?array
    {
        try {
            return Http::asForm()
                ->timeout((int) config('laracaptcha.timeout', 5))
                ->post($endpoint, array_filter([
                    'secret' => $this->config['secret'] ?? '',
                    'response' => $token,
                    'remoteip' => $ip,
                ]))
                ->json() ?? [];
        } catch (ConnectionException) {
            return null;
        }
    }

    /**
     * Refuse a passing result whose token was solved on a site not listed in
     * `laracaptcha.hostnames`.
     *
     * Fails closed: with a list configured, a response that names no hostname
     * at all is a mismatch too. With no list, the result goes back untouched.
     *
     * @param VerificationResult $result
     * @param array<string, mixed> $data
     * @return VerificationResult
     */
    protected function checkHostname(VerificationResult $result, array $data): VerificationResult
    {
        $allowed = array_map('strtolower', (array) config('laracaptcha.hostnames', []));

        if (! $result->success || $allowed === []) {
            return $result;
        }

        if (in_array(strtolower((string) ($data['hostname'] ?? '')), $allowed, true)) {
            return $result;
        }

        return new VerificationResult(
            success: false,
            score: $result->score,
            errorCodes: array_merge($result->errorCodes, ['hostname-mismatch']),
            raw: $data,
        );
    }
}
