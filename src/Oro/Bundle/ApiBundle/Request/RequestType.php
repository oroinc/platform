<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Supported types of Data API requests.
 */
final class RequestType
{
    /**
     * REST API request
     */
    const REST = 'rest';

    /**
     * A request that conforms JSON API specification
     * @see http://jsonapi.org
     */
    const JSON_API = 'json_api';
}
