<?php

namespace App\Services\Analytics;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Builds the Guzzle/HTTP client used by the platform connectors.
 *
 * Local PHP installs often ship without a CA bundle (curl.cainfo unset),
 * which makes outbound HTTPS fail with "unable to get local issuer
 * certificate". We point curl at a project-bundled bundle instead, with an
 * env toggle to disable verification in development if ever needed.
 */
class HttpClientFactory
{
    public static function make(string $baseUrl): PendingRequest
    {
        $options = [];

        if (config('services.verify_ssl', true)) {
            $ca = storage_path('certs/cacert.pem');
            $options['verify'] = is_file($ca) ? $ca : true;
        } else {
            $options['verify'] = false;
        }

        return Http::baseUrl($baseUrl)->timeout(10)->withOptions($options);
    }
}
