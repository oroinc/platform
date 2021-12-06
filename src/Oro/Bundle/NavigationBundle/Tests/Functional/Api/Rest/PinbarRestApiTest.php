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

        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_post_navigationitems', ['type' => 'pinbar']),
            [
                'url' => $pin->getUrl(),
                'title' => $pin->getTitle(),
                'position' => $pin->getPosition(),
                'type' => 'pinbar',
            ],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 422);

        $resultJson = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            ['message' => 'This pin already exists'],
            $resultJson
        );
    }

    public function testPutWhenPinWithUrl(): void
    {
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $updatedPintab = [
            'url' => $urlGenerator->generate('oro_config_configuration_system', ['sample_key' => 'sample_value']),
        ];
        $this->client->jsonRequest(
            'PUT',
            $this->getUrl(
                'oro_api_put_navigationitems_id',
                ['type' => 'pinbar', 'itemId' => $this->getItemId()]
            ),
            $updatedPintab,
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);

        $resultJson = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(0, $resultJson);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_navigationitems', ['type' => 'pinbar']),
            [],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $resultJson = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $expectedUrl = $urlGenerator->generate(
            'oro_config_configuration_system',
            ['restore' => 1, 'sample_key' => 'sample_value']
        );
        $data = [];
        foreach ($resultJson as $item) {
            if ($item['url'] === $expectedUrl) {
                $data = $item;
                break;
            }
        }
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('parent_id', $data);
        unset($data['id'], $data['parent_id']);
        self::assertEquals(
            [
                'url'                  => $expectedUrl,
                'title'                => '{"type":"pinbar","route":"oro_config_configuration_system","title":{'
                    . '"template":"oro.config.menu.system_configuration.label - oro.user.menu.system_tab.label",'
                    . '"short_template":"oro.config.menu.system_configuration.label","params":[]},"position":0}',
                'type'                 => 'pinbar',
                'title_rendered'       => 'Configuration - System',
                'title_rendered_short' => 'Configuration'
            ],
            $data
        );
    }

    public function testGetPinbar(): void
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_navigationitems', ['type' => 'pinbar']),
            [],
            self::generateWsseAuthHeader(LoadUserData::USER_NAME_2, LoadUserData::USER_PASSWORD_2)
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        $resultJson = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('id', $resultJson[0]);
        $this->assertArrayHasKey('parent_id', $resultJson[0]);
        unset($resultJson[0]['id'], $resultJson[0]['parent_id']);
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                'url'                  => $urlGenerator->generate('oro_config_configuration_system', ['restore' => 1]),
                'title'                => '{"type":"pinbar","route":"oro_config_configuration_system","title":{'
                    . '"template":"oro.config.menu.system_configuration.label - oro.user.menu.system_tab.label",'
                    . '"short_template":"oro.config.menu.system_configuration.label","params":[]},"position":0}',
                'type'                 => 'pinbar',
                'title_rendered'       => 'Configuration - System',
                'title_rendered_short' => 'Configuration'
            ],
            $resultJson[0]
        );
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
