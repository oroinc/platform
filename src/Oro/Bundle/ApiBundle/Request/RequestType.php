<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Supported API request types.
 */
final class RequestType
{
    /**
     * REST API that can be used to work with plain PHP objects
     */
    const REST_PLAIN = 'rest_plain';

    /**
     * REST API conforms JSON API specification
     * @see http://jsonapi.org
     */
    const REST_JSON_API = 'rest_json_api';
}
