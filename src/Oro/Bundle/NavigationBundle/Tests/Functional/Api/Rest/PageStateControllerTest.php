<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\PageStateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PageStateControllerTest extends WebTestCase
{
    private static array $entity = [];

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            PageStateData::class,
        ]);
    }

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

        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_post_pagestate'),
            self::$entity,
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

        self::$entity['data'] = '["test"]';
        self::$entity['pagestate']['data'] = '["test"]';

        $this->client->jsonRequest(
            'PUT',
            $this->getUrl('oro_api_put_pagestate', ['id' => self::$entity['id']]),
            self::$entity,
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, 204);
    }

    /**
     * @depends testPut
     */
    public function testGet()
    {
        $this->assertNotEmpty(self::$entity);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_pagestate', ['id' => self::$entity['id']]),
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $resultJson = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('id', $resultJson);
        $this->assertArrayHasKey('created_at', $resultJson);
        $this->assertArrayHasKey('updated_at', $resultJson);
        unset($resultJson['id'], $resultJson['created_at'], $resultJson['updated_at']);
        $this->assertEquals(
            [
                'data' => '["test"]',
                'page_id' => 'dGVzdDE=',
                'page_hash' => 'd134a05c9bcd7ff16921f5267748513b'
            ],
            $resultJson
        );
    }

    public function testGetWhenAnotherUser()
    {
        $entity = $this->getReference(PageStateData::PAGE_STATE_1);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_pagestate', ['id' => $entity->getId()]),
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    /**
     * @depends testPut
     */
    public function testDelete()
    {
        $this->assertNotEmpty(self::$entity);

        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl('oro_api_delete_pagestate', ['id' => self::$entity['id']]),
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();

        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
