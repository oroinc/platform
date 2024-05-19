<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlain;

use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Symfony\Component\HttpFoundation\Response;

class InvalidRequestTypeTest extends RestPlainApiTestCase
{
    public function testUnsupportedAcceptHeader(): void
    {
        $response = $this->request(
            'GET',
            $this->getUrl(
                $this->getListRouteName(),
                ['entity' => $this->getEntityType(TestProduct::class)]
            ),
            [],
            ['HTTP_ACCEPT' => 'application/xml'],
            ''
        );
        $this->assertResponseContainsValidationError(
            [
                'title'  => 'not acceptable http exception',
                'detail' => 'Only JSON representation of the requested resource is supported.'
            ],
            $response,
            Response::HTTP_NOT_ACCEPTABLE
        );
    }

    public function testUnsupportedContentTypeHeader(): void
    {
        $response = $this->request(
            'POST',
            $this->getUrl(
                $this->getListRouteName(),
                ['entity' => $this->getEntityType(TestProduct::class)]
            ),
            [],
            ['CONTENT_TYPE' => 'application/xml'],
            ''
        );
        $this->assertResponseContainsValidationError(
            [
                'title'  => 'not acceptable http exception',
                'detail' => 'The "Content-Type" request header must be "application/json" if specified.'
            ],
            $response,
            Response::HTTP_NOT_ACCEPTABLE
        );
    }
}
