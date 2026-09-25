<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Tests\Functional\OpenApi;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OpenApiSpecificationControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    public function testDeleteDoesNotAllowGetRequest(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('oro_openapi_specification_delete', ['id' => 1])
        );

        self::assertResponseStatusCodeEquals(
            $this->client->getResponse(),
            Response::HTTP_METHOD_NOT_ALLOWED
        );
    }
}
