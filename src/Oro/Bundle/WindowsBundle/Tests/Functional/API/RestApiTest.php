<?php

namespace Oro\Bundle\WindowsBundle\Tests\Functional\API;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestApiTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected static $entity;

    public function setUp()
    {
        $this->client = self::createClient();
    }

    /**
     * Test POST
     */
    public function testPost()
    {
        self::$entity = array(
            'data' => array(
                'position' => '0',
                'title' => 'Some title'
            )
        );

        $this->client->request(
            'POST',
            $this->client->generate('oro_api_post_windows'),
            self::$entity,
            array(),
            $this->generateWsseHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 201);

        $resultJson = json_decode($result->getContent(), true);

        $this->assertArrayHasKey("id", $resultJson);
        $this->assertGreaterThan(0, $resultJson["id"]);

        self::$entity['id'] = $resultJson["id"];
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
            $this->client->generate('oro_api_put_windows', array('windowId' => self::$entity['id'])),
            self::$entity,
            array(),
            $this->generateWsseHeader()
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
            $this->client->generate('oro_api_get_windows'),
            array(),
            array(),
            $this->generateWsseHeader()
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
            $this->client->generate('oro_api_delete_windows', array('windowId' => self::$entity['id'])),
            array(),
            array(),
            $this->generateWsseHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 204);
        $this->assertEmpty($result->getContent());
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
            $this->client->generate('oro_api_put_windows', array('windowId' => self::$entity['id'])),
            self::$entity,
            array(),
            $this->generateWsseHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);

        $this->client->restart();

        $this->client->request(
            'DELETE',
            $this->client->generate('oro_api_delete_windows', array('windowId' => self::$entity['id'])),
            array(),
            array(),
            $this->generateWsseHeader()
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

        $requests = array(
            'GET'    => $this->client->generate('oro_api_get_windows'),
            'POST'   => $this->client->generate('oro_api_post_windows'),
            'PUT'    => $this->client->generate('oro_api_put_windows', array('windowId' => self::$entity['id'])),
            'DELETE' => $this->client->generate('oro_api_delete_windows', array('windowId' => self::$entity['id'])),
        );

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

        $requests = array(
            'POST' => $this->client->generate('oro_api_post_windows'),
            'PUT'  => $this->client->generate('oro_api_put_windows', array('windowId' => self::$entity['id'])),
        );

        foreach ($requests as $requestType => $url) {
            $this->client->request(
                $requestType,
                $url,
                array(),
                array(),
                $this->generateWsseHeader()
            );

            /** @var $response Response */
            $response = $this->client->getResponse();

            $this->assertJsonResponseStatusCodeEquals($response, 400);

            $responseJson = json_decode($response->getContent(), true);

            $this->assertArrayHasKey('message', $responseJson);
            $this->assertEquals('Wrong JSON inside POST body', $responseJson['message']);

            $this->client->restart();
        }
    }
}
