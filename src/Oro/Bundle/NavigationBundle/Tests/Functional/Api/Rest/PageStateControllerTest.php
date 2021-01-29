<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\PageStateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PageStateControllerTest extends WebTestCase
{
    /**
     * @var array
     */
    protected static $entity;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            PageStateData::class,
        ]);
    }

    /**
     * Test POST
     */
    public function testPost()
    {
        self::$entity = [
            'pageId' => base64_encode('test1'),
            'data' => '[]',
            'pagestate' => [
                'data' => '[]',
                'pageId' => base64_encode('test1')
            ]
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_pagestate'),
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

        self::$entity['data'] = '["test"]';
        self::$entity['pagestate']['data'] = '["test"]';

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_pagestate', ['id' => self::$entity['id']]),
            self::$entity,
            [],
            $this->generateWsseAuthHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, 204);
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
            $this->getUrl('oro_api_get_pagestate', ['id' => self::$entity['id']]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $resultJson = json_decode($result->getContent(), true);

        $this->assertNotEmpty($resultJson);
        $this->assertArrayHasKey('id', $resultJson);
        $this->assertArrayNotHasKey('user', $resultJson);
    }

    /**
     * Test GET PageState of another user
     */
    public function testGetWhenAnotherUser()
    {
        $entity = $this->getReference(PageStateData::PAGE_STATE_1);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_pagestate', ['id' => $entity->getId()]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    /**
     * Test DELETE
     *
     * @depends testPut
     */
    public function testDelete()
    {
        $this->assertNotEmpty(self::$entity);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_pagestate', ['id' => self::$entity['id']]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
