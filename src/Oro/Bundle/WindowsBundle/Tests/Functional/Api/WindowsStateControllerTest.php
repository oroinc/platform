<?php

namespace Oro\Bundle\WindowsBundle\Tests\Functional\Api;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class WindowsStateControllerTest extends WebTestCase
{
    private static array $entity = [];

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testPost()
    {
        self::$entity = [
            'data' => [
                'position' => '0',
                'title'    => 'Some title',
                'url'      => '/path'
            ],
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_windows'),
            self::$entity,
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 201);

        $resultJson = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('id', $resultJson);
        $this->assertGreaterThan(0, $resultJson['id']);

        self::$entity['id'] = $resultJson['id'];
    }

    /**
     * @depends testPost
     */
    public function testPut()
    {
        $this->assertNotEmpty(self::$entity);

        self::$entity['data']['position'] = 100;

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_windows', ['windowId' => self::$entity['id']]),
            self::$entity,
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $resultJson = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(0, $resultJson);
    }

    /**
     * @depends testPut
     */
    public function testGet()
    {
        $this->assertNotEmpty(self::$entity);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_windows'),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $resultJson = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('id', $resultJson[0]);
        $this->assertArrayHasKey('created_at', $resultJson[0]);
        $this->assertArrayHasKey('updated_at', $resultJson[0]);
        unset($resultJson[0]['id'], $resultJson[0]['created_at'], $resultJson[0]['updated_at']);
        $this->assertEquals(
            [
                [
                    'data'                  => [
                        'url'      => '/path',
                        'title'    => 'Some title',
                        'cleanUrl' => '/path',
                        'position' => 100
                    ],
                    'rendered_successfully' => false
                ]
            ],
            $resultJson
        );
    }

    /**
     * @depends testPut
     */
    public function testDelete()
    {
        $this->assertNotEmpty(self::$entity);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_windows', ['windowId' => self::$entity['id']]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();

        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }

    /**
     * @depends testDelete
     */
    public function testNotFound()
    {
        $this->assertNotEmpty(self::$entity);

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_windows', ['windowId' => self::$entity['id']]),
            self::$entity,
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);

        $this->client->restart();

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_windows', ['windowId' => self::$entity['id']]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    /**
     * @depends testNotFound
     */
    public function testUnauthorized()
    {
        $this->assertNotEmpty(self::$entity);

        $requests = [
            'GET'    => $this->getUrl('oro_api_get_windows'),
            'POST'   => $this->getUrl('oro_api_post_windows'),
            'PUT'    => $this->getUrl('oro_api_put_windows', ['windowId' => self::$entity['id']]),
            'DELETE' => $this->getUrl('oro_api_delete_windows', ['windowId' => self::$entity['id']]),
        ];

        foreach ($requests as $requestType => $url) {
            $this->client->request($requestType, $url);

            $response = $this->client->getResponse();

            $this->assertEquals(401, $response->getStatusCode());

            $this->client->restart();
        }
    }

    /**
     * @depends testNotFound
     */
    public function testEmptyBody()
    {
        $this->assertNotEmpty(self::$entity);

        $requests = [
            'POST' => $this->getUrl('oro_api_post_windows'),
            'PUT'  => $this->getUrl('oro_api_put_windows', ['windowId' => self::$entity['id']]),
        ];

        foreach ($requests as $requestType => $url) {
            $this->client->request(
                $requestType,
                $url,
                [],
                [],
                $this->generateWsseAuthHeader()
            );

            $response = $this->client->getResponse();

            $this->assertJsonResponseStatusCodeEquals($response, 400);

            $responseJson = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            // The original exception message is returned only if functional tests are running in debug mode
            if ($this->client->getKernel()->isDebug()) {
                $this->assertEquals('Wrong JSON inside POST body', $responseJson['message']);
            }

            $this->client->restart();
        }
    }

    public function testEmptyJsonInRequestData()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_windows'),
            [],
            [],
            $this->generateWsseAuthHeader(),
            ''
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        self::assertResponseContentTypeEquals($response, 'application/json');
        self::assertEquals(
            ['code' => 400],
            self::jsonToArray($response->getContent())
        );
    }

    public function testInvalidJsonInRequestData()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_windows'),
            [],
            [],
            $this->generateWsseAuthHeader(),
            '{"data": {"type": test"}}'
        );
        $response = $this->client->getResponse();
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
}
