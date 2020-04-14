<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RestApiTest extends WebTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    protected function setUp(): void
    {
        $this->initClient();
    }

    /**
     * Data provider
     *
     * @return array
     */
    public static function navigationItemsProvider()
    {
        return array(
            array('pinbar'),
            array('favorite'),
        );
    }

    public function testPostPinbarWithLongUrlPath()
    {
        $path = $this->getQueryPath();
        $url = 'http://some-url.com' . $path;
        $parameters = [
            'url' => $url,
            'title' => 'Title',
            'position' => 0,
            'type' => 'pinbar'
        ];

        $result = $this->postNavigationItem('pinbar', $parameters);

        $this->assertUrl((int)$result['id'], $path . '?restore=1');
    }

    /**
     * Test POST
     *
     * @dataProvider navigationItemsProvider
     */
    public function testPost($itemType)
    {
        self::$entities[$itemType] = array(
            'url' => 'http://some-url.com',
            'title' => 'Title',
            'position' => 0,
            'type' => $itemType
        );

        $resultJson = $this->postNavigationItem($itemType, self::$entities[$itemType]);

        $this->assertArrayHasKey('id', $resultJson);
        $this->assertGreaterThan(0, $resultJson['id']);

        self::$entities[$itemType]['id'] = $resultJson['id'];
    }

    /**
     * Test POST when pin already exists.
     *
     * @depends testPost
     */
    public function testPostPinbarWhenAlreadyExists()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_navigationitems', ['type' => 'pinbar']),
            self::$entities['pinbar'],
            [],
            $this->generateWsseAuthHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 422);

        $resultJson = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('message', $resultJson);
        $this->assertEquals('This pin already exists', $resultJson['message']);
    }

    /**
     * Test PUT
     *
     * @depends testPost
     * @dataProvider navigationItemsProvider
     */
    public function testPut($itemType)
    {
        $this->assertNotEmpty(self::$entities[$itemType]);

        $updatedPintab = array(
            'position' => 100
        );

        $this->client->request(
            'PUT',
            $this->getUrl(
                'oro_api_put_navigationitems_id',
                array('type' => $itemType, 'itemId' => self::$entities[$itemType]['id'])
            ),
            $updatedPintab,
            array(),
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
     * @dataProvider navigationItemsProvider
     */
    public function testGet($itemType)
    {
        $this->assertNotEmpty(self::$entities[$itemType]);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_navigationitems', array('type' => $itemType)),
            array(),
            array(),
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
     * Test GET pinbar
     *
     * @depends testPut
     */
    public function testGetPinbar()
    {
        $this->assertNotEmpty(self::$entities['pinbar']);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_navigationitems', ['type' => 'pinbar']),
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
        $this->assertArrayHasKey('title_rendered', $resultJson[0]);
        $this->assertArrayHasKey('title_rendered_short', $resultJson[0]);
    }

    /**
     * Test DELETE
     *
     * @depends testPut
     * @dataProvider navigationItemsProvider
     */
    public function testDelete($itemType)
    {
        $this->assertNotEmpty(self::$entities[$itemType]);

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_delete_navigationitems_id',
                array('type' => $itemType, 'itemId' => self::$entities[$itemType]['id'])
            ),
            array(),
            array(),
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
     * @dataProvider navigationItemsProvider
     */
    public function testNotFound($itemType)
    {
        $this->assertNotEmpty(self::$entities[$itemType]);

        $this->client->request(
            'PUT',
            $this->getUrl(
                'oro_api_put_navigationitems_id',
                array('type' => $itemType, 'itemId' => self::$entities[$itemType]['id'])
            ),
            self::$entities[$itemType],
            array(),
            $this->generateWsseAuthHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);

        $this->client->restart();

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_delete_navigationitems_id',
                array('type' => $itemType, 'itemId' => self::$entities[$itemType]['id'])
            ),
            array(),
            array(),
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
     * @dataProvider navigationItemsProvider
     */
    public function testUnauthorized($itemType)
    {
        $this->assertNotEmpty(self::$entities[$itemType]);

        $requests = array(
            'GET'    => $this->getUrl(
                'oro_api_get_navigationitems',
                array('type' => $itemType)
            ),
            'POST'   => $this->getUrl(
                'oro_api_post_navigationitems',
                array('type' => $itemType)
            ),
            'PUT'    => $this->getUrl(
                'oro_api_put_navigationitems_id',
                array('type' => $itemType, 'itemId' => self::$entities[$itemType]['id'])
            ),
            'DELETE' => $this->getUrl(
                'oro_api_delete_navigationitems_id',
                array('type' => $itemType, 'itemId' => self::$entities[$itemType]['id'])
            ),
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
     * @dataProvider navigationItemsProvider
     */
    public function testEmptyBody($itemType)
    {
        $this->assertNotEmpty(self::$entities[$itemType]);

        $requests = array(
            'POST' => $this->getUrl(
                'oro_api_post_navigationitems',
                array('type' => $itemType)
            ),
            'PUT'  => $this->getUrl(
                'oro_api_put_navigationitems_id',
                array('type' => $itemType, 'itemId' => self::$entities[$itemType]['id'])
            ),
        );

        foreach ($requests as $requestType => $url) {
            $this->client->request(
                $requestType,
                $url,
                array(),
                array(),
                $this->generateWsseAuthHeader()
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

    /**
     * @param string $itemType
     * @param array $parameters
     * @return array
     */
    private function postNavigationItem(string $itemType, array $parameters): array
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_navigationitems', array('type' => $itemType)),
            $parameters,
            array(),
            $this->generateWsseAuthHeader()
        );

        /** @var $result Response */
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 201);

        return json_decode($result->getContent(), true);
    }

    /**
     * @return string
     */
    private function getQueryPath(): string
    {
        // forms query path of 8150 characters long
        return '/' . str_repeat('some_part/', 815);
    }

    /**
     * @param int $id
     * @param string $url
     */
    private function assertUrl(int $id, string $url): void
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(PinbarTab::class);
        /** @var PinbarTab $entity */
        $entity = $em->find(PinbarTab::class, $id);

        $this->assertEquals($url, $entity->getItem()->getUrl());
    }
}
