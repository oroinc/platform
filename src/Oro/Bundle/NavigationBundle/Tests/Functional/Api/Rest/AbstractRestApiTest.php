<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\NavigationItemData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
abstract class AbstractRestApiTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([NavigationItemData::class]);
    }

    abstract protected function getItemType(): string;

    abstract protected function getItemId(): int;

    public function testPost(): void
    {
        $entityData = [
            'url' => '/sample/url',
            'title' => 'Title',
            'position' => 0,
            'type' => $this->getItemType(),
        ];

        $resultJson = $this->postNavigationItem($entityData);

        self::assertArrayHasKey('id', $resultJson);
        self::assertGreaterThan(0, $resultJson['id']);
    }

    public function testPut(): void
    {
        $updatedPintab = ['position' => 100];

        $this->client->jsonRequest(
            'PUT',
            $this->getUrl(
                'oro_api_put_navigationitems_id',
                ['type' => $this->getItemType(), 'itemId' => $this->getItemId()]
            ),
            $updatedPintab,
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);

        $resultJson = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(0, $resultJson);
    }

    public function testGet(): void
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_navigationitems', ['type' => $this->getItemType()]),
            [],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $resultJson = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($resultJson);
        self::assertArrayHasKey('id', $resultJson[0]);
        self::assertContains($this->getItemId(), array_column($resultJson, 'id'));
    }

    public function testDelete(): void
    {
        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl(
                'oro_api_delete_navigationitems_id',
                ['type' => $this->getItemType(), 'itemId' => $this->getItemId()]
            ),
            [],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();

        self::assertEmptyResponseStatusCodeEquals($result, 204);
    }

    public function testNotFound(): void
    {
        $this->client->jsonRequest(
            'PUT',
            $this->getUrl(
                'oro_api_put_navigationitems_id',
                ['type' => $this->getItemType(), 'itemId' => PHP_INT_MAX]
            ),
            ['url' => 'sample/url'],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 404);

        $this->client->restart();

        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl(
                'oro_api_delete_navigationitems_id',
                ['type' => $this->getItemType(), 'itemId' => PHP_INT_MAX]
            ),
            [],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );
        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 404);
    }

    public function testUnauthorized(): void
    {
        $requests = [
            'GET' => $this->getUrl(
                'oro_api_get_navigationitems',
                ['type' => $this->getItemType()]
            ),
            'POST' => $this->getUrl(
                'oro_api_post_navigationitems',
                ['type' => $this->getItemType()]
            ),
            'PUT' => $this->getUrl(
                'oro_api_put_navigationitems_id',
                ['type' => $this->getItemType(), 'itemId' => $this->getItemId()]
            ),
            'DELETE' => $this->getUrl(
                'oro_api_delete_navigationitems_id',
                ['type' => $this->getItemType(), 'itemId' => $this->getItemId()]
            ),
        ];

        foreach ($requests as $requestType => $url) {
            $this->client->jsonRequest($requestType, $url);

            /** @var $result Response */
            $response = $this->client->getResponse();

            self::assertEquals(401, $response->getStatusCode());

            $this->client->restart();
        }
    }

    public function testEmptyBody(): void
    {
        $requests = [
            'POST' => $this->getUrl(
                'oro_api_post_navigationitems',
                ['type' => $this->getItemType()]
            ),
            'PUT' => $this->getUrl(
                'oro_api_put_navigationitems_id',
                ['type' => $this->getItemType(), 'itemId' => $this->getItemId()]
            ),
        ];

        foreach ($requests as $requestType => $url) {
            $this->client->jsonRequest(
                $requestType,
                $url,
                [],
                self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
            );

            $response = $this->client->getResponse();

            self::assertJsonResponseStatusCodeEquals($response, 400);

            $responseJson = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

            self::assertArrayHasKey('message', $responseJson);
            self::assertEquals('Wrong JSON inside POST body', $responseJson['message']);

            $this->client->restart();
        }
    }

    protected function postNavigationItem(array $parameters): array
    {
        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_post_navigationitems', ['type' => $this->getItemType()]),
            $parameters,
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 201);

        return json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
