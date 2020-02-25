<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlain;

use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Symfony\Component\HttpFoundation\Response;

class InvalidRequestDataTest extends RestPlainApiTestCase
{
    public function testEmptyJsonInRequestData()
    {
        $response = $this->request(
            'POST',
            $this->getUrl(
                $this->getListRouteName(),
                ['entity' => $this->getEntityType(TestProduct::class)]
            ),
            [],
            [],
            ''
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        self::assertResponseContentTypeEquals($response, self::JSON_CONTENT_TYPE);
        self::assertEquals(
            [
                [
                    'title'  => 'request data constraint',
                    'detail' => 'The request data should not be empty'
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testInvalidJsonInRequestData()
    {
        $response = $this->request(
            'POST',
            $this->getUrl(
                $this->getListRouteName(),
                ['entity' => $this->getEntityType(TestProduct::class)]
            ),
            [],
            [],
            '{"data": {"type": test"}}'
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        self::assertResponseContentTypeEquals($response, 'application/json');
        self::assertEquals(
            [
                'code'    => 400,
                'message' => 'Invalid json message received.'
                    . ' Parsing error in [1:22]. Expected \'null\'. Got: test'
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testInvalidJsonWithInvalidUtf8CharacterInRequestData()
    {
        $response = $this->request(
            'POST',
            $this->getUrl(
                $this->getListRouteName(),
                ['entity' => $this->getEntityType(TestProduct::class)]
            ),
            [],
            [],
            '{"data": ▿{"type": test"}}'
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        self::assertResponseContentTypeEquals($response, 'application/json');
        self::assertEquals(
            [
                'code'    => 400,
                'message' => 'Invalid json message received.'
                    . ' Parsing error in [1:10]. Unexpected character for value: ?'
            ],
            self::jsonToArray($response->getContent())
        );
    }
}
