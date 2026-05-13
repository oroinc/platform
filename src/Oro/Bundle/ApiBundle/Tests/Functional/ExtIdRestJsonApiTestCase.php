<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

/**
 * The base class for "JSON:API EXT ID" API functional tests.
 * The "JSON:API EXT ID" API is REST API that conforms the JSON:API specification
 * and where some API resources are identified by an identifier provided by an external system.
 */
class ExtIdRestJsonApiTestCase extends RestJsonApiTestCase
{
    #[\Override]
    protected function request(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
        ?string $content = null
    ): Response {
        $server['HTTP_X-Integration-Type'] = 'ext_id';

        return parent::request($method, $uri, $parameters, $server, $content);
    }
}
