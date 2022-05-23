<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSecurity;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class UnauthorizedTest extends RestJsonApiTestCase
{
    private const WWW_AUTHENTICATE_HEADER_VALUE = 'WSSE realm="Secured API", profile="UsernameToken"';

    /**
     * {@inheritdoc}
     */
    protected function getWsseAuthHeader(): array
    {
        return self::generateWsseAuthHeader('NotExistingUser');
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'testapientity1'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertSame('', $response->getContent());
        self::assertResponseHeader($response, 'WWW-Authenticate', self::WWW_AUTHENTICATE_HEADER_VALUE);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => '1'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertSame('', $response->getContent());
        self::assertResponseHeader($response, 'WWW-Authenticate', self::WWW_AUTHENTICATE_HEADER_VALUE);
    }

    public function testCreate()
    {
        $response = $this->post(
            ['entity' => 'testapientity1'],
            [
                'data' => [
                    'type'       => 'testapientity1',
                    'id'         => '1',
                    'attributes' => ['name' => 'New']
                ]
            ],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertSame('', $response->getContent());
        self::assertResponseHeader($response, 'WWW-Authenticate', self::WWW_AUTHENTICATE_HEADER_VALUE);
    }

    public function testUpdate()
    {
        $response = $this->patch(
            ['entity' => 'testapientity1', 'id' => '1'],
            [
                'data' => [
                    'type'       => 'testapientity1',
                    'id'         => '1',
                    'attributes' => ['name' => 'Updated']
                ]
            ],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertSame('', $response->getContent());
        self::assertResponseHeader($response, 'WWW-Authenticate', self::WWW_AUTHENTICATE_HEADER_VALUE);
    }

    public function testDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'testapientity1'],
            ['filter' => ['id' => '1']],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertSame('', $response->getContent());
        self::assertResponseHeader($response, 'WWW-Authenticate', self::WWW_AUTHENTICATE_HEADER_VALUE);
    }

    public function testDelete()
    {
        $response = $this->delete(
            ['entity' => 'testapientity1', 'id' => '1'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertSame('', $response->getContent());
        self::assertResponseHeader($response, 'WWW-Authenticate', self::WWW_AUTHENTICATE_HEADER_VALUE);
    }

    public function testOptionsForList()
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'testapientity1']
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST, DELETE');
    }

    public function testOptionsForItem()
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'testapientity1', 'id' => '1']
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, DELETE');
    }
}
