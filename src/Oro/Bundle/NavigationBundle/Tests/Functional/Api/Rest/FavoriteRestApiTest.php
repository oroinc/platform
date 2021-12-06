<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\NavigationItemData;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @dbIsolationPerTest
 */
class FavoriteRestApiTest extends AbstractRestApiTest
{
    protected function getItemType(): string
    {
        return 'favorite';
    }

    protected function getItemId(): int
    {
        return $this->getReference(NavigationItemData::NAVIGATION_ITEM_FAVORITE_1)->getId();
    }

    public function testGetFavorites(): void
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
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals(
            [
                [
                    'id'    => $this->getItemId(),
                    'url'   => $urlGenerator->generate('oro_user_role_create', ['restore' => 1]),
                    'title' => '{"type":"favorite","route":"oro_user_role_create","title":"Roles","position":0}',
                    'type'  => $this->getItemType()
                ]
            ],
            $resultJson
        );
    }
}
