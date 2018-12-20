<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

/**
 * Provides the names of CORS preflight request headers.
 * @link https://www.w3.org/TR/cors/
 */
final class CorsHeaders
{
    public const ORIGIN                           = 'Origin';
    public const ACCESS_CONTROL_REQUEST_METHOD    = 'Access-Control-Request-Method';
    public const ACCESS_CONTROL_REQUEST_HEADERS   = 'Access-Control-Request-Headers';
    public const ACCESS_CONTROL_ALLOW_ORIGIN      = 'Access-Control-Allow-Origin';
    public const ACCESS_CONTROL_ALLOW_METHODS     = 'Access-Control-Allow-Methods';
    public const ACCESS_CONTROL_ALLOW_HEADERS     = 'Access-Control-Allow-Headers';
    public const ACCESS_CONTROL_ALLOW_CREDENTIALS = 'Access-Control-Allow-Credentials';
    public const ACCESS_CONTROL_EXPOSE_HEADERS    = 'Access-Control-Expose-Headers';
    public const ACCESS_CONTROL_MAX_AGE           = 'Access-Control-Max-Age';
}
