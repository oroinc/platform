<?php

namespace Oro\Bundle\WindowsBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class WindowsStateController extends WebTestCase
{
    /**
     * @var array
     */
    protected static $entity;

    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * Test POST
     */
    public function testPost()
    {
        self::$entity = [
            'data' => [
                'position' => '0',
                'title' => 'Some title',
                'url' => '/path'
            ],
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_windows'),
            self::$entity,
            [],
            $this->generateWsseAuthHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 201);

        $resultJson = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('id', $resultJson);
        $this->assertGreaterThan(0, $resultJson['id']);

        self::$entity['id'] = $resultJson['id'];
    }

    /**
     * Test PUT
     *
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

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $resultJson = json_decode($result->getContent(), true);

        $this->assertCount(0, $resultJson);
    }

    /**
     * Test GET
     *
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

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $resultJson = json_decode($result->getContent(), true);

        $this->assertNotEmpty($resultJson);
        $this->assertArrayHasKey('id', $resultJson[0]);
    }

    /**
     * Test DELETE
     *
     * @depends testPut
     */
    public function testDelete($itemType)
    {
        $this->assertNotEmpty(self::$entity);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_windows', ['windowId' => self::$entity['id']]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }

    /**
     * Test 404
     *
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

        /** @var $result Response */
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

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    /**
     * Test Unauthorized
     *
     * @depends testNotFound
     */
    public function testUnauthorized()
    {
        $this->assertNotEmpty(self::$entity);

        $requests = [
            'GET' => $this->getUrl('oro_api_get_windows'),
            'POST' => $this->getUrl('oro_api_post_windows'),
            'PUT' => $this->getUrl('oro_api_put_windows', ['windowId' => self::$entity['id']]),
            'DELETE' => $this->getUrl('oro_api_delete_windows', ['windowId' => self::$entity['id']]),
        ];

        foreach ($requests as $requestType => $url) {
            $this->client->request($requestType, $url);

            /** @var $result Response */
            $response = $this->client->getResponse();

            $this->assertEquals(401, $response->getStatusCode());

            $this->client->restart();
        }
    }

    /**
     * Test Empty Body error
     *
     * @depends testNotFound
     */
    public function testEmptyBody()
    {
        $this->assertNotEmpty(self::$entity);

        $requests = [
            'POST' => $this->getUrl('oro_api_post_windows'),
            'PUT' => $this->getUrl('oro_api_put_windows', ['windowId' => self::$entity['id']]),
        ];

        foreach ($requests as $requestType => $url) {
            $this->client->request(
                $requestType,
                $url,
                [],
                [],
                $this->generateWsseAuthHeader()
            );

            /** @var $response Response */
            $response = $this->client->getResponse();

            $this->assertJsonResponseStatusCodeEquals($response, 400);

            $responseJson = json_decode($response->getContent(), true);

            // The original exception message is returned only if functional tests are running in debug mode
            if ($this->client->getKernel()->isDebug()) {
                $this->assertEquals('Wrong JSON inside POST body', $responseJson['message']);
            }

            $this->client->restart();
        }
    }
}
