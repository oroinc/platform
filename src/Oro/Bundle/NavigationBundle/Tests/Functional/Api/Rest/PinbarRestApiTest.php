<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\NavigationItemData;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\PinbarTabData;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @dbIsolationPerTest
 */
class PinbarRestApiTest extends AbstractRestApiTest
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([PinbarTabData::class]);
    }

    protected function getItemType(): string
    {
        return 'pinbar';
    }

    protected function getItemId(): int
    {
        return $this->getReference(PinbarTabData::PINBAR_TAB_1)->getId();
    }

    public function testPostPinbarWithLongUrlPath(): void
    {
        $path = $this->getQueryPath();
        $url = 'http://some-url.com' . $path;
        $parameters = [
            'url' => $url,
            'title' => 'Title',
            'position' => 0,
            'type' => 'pinbar',
        ];

        $result = $this->postNavigationItem($parameters);

        $this->assertUrl((int)$result['id'], $path . '?restore=1');
    }

    public function testPostPinbarWhenAlreadyExists(): void
    {
        $pin = $this->getReference(NavigationItemData::NAVIGATION_ITEM_PINBAR_1);

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_navigationitems', ['type' => 'pinbar']),
            [
                'url' => $pin->getUrl(),
                'title' => $pin->getTitle(),
                'position' => $pin->getPosition(),
                'type' => 'pinbar',
            ],
            [],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 422);

        $resultJson = json_decode($result->getContent(), true);

        self::assertArrayHasKey('message', $resultJson);
        self::assertEquals('This pin already exists', $resultJson['message']);
    }

    public function testPutWhenPinWithUrl(): void
    {
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $updatedPintab = [
            'url' => $urlGenerator->generate('oro_config_configuration_system', ['sample_key' => 'sample_value']),
        ];
        $this->client->request(
            'PUT',
            $this->getUrl(
                'oro_api_put_navigationitems_id',
                ['type' => 'pinbar', 'itemId' => $this->getItemId()]
            ),
            $updatedPintab,
            [],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);

        $resultJson = json_decode($result->getContent(), true);

        self::assertCount(0, $resultJson);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_navigationitems', ['type' => 'pinbar']),
            [],
            [],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $resultJson = json_decode($this->client->getResponse()->getContent(), true);
        self::assertNotEmpty($resultJson);
        self::assertContains(
            $urlGenerator->generate(
                'oro_config_configuration_system',
                ['restore' => 1, 'sample_key' => 'sample_value']
            ),
            array_column($resultJson, 'url', 'id')
        );
    }

    public function testGetPinbar(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_navigationitems', ['type' => 'pinbar']),
            [],
            [],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $resultJson = json_decode($result->getContent(), true);
        self::assertNotEmpty($resultJson);
        self::assertArrayHasKey('id', $resultJson[0]);
        self::assertArrayHasKey('title_rendered', $resultJson[0]);
        self::assertArrayHasKey('title_rendered_short', $resultJson[0]);
    }

    private function getQueryPath(): string
    {
        // forms query path of 8150 characters long
        return '/' . str_repeat('some_part/', 815);
    }

    private function assertUrl(int $id, string $url): void
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(PinbarTab::class);
        $entity = $em->find(PinbarTab::class, $id);

        self::assertEquals($url, $entity->getItem()->getUrl());
    }
}
