<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\NavigationItemData;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\PinbarTabData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;

class PinbarTabRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([PinbarTabData::class]);
    }

    private function getRepository(): PinbarTabRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(PinbarTab::class);
    }

    /**
     * @dataProvider countNavigationItemsWhenNoItemsDataProvider
     */
    public function testCountNavigationItemsWhenNoItems(string $url, int $expectedCount): void
    {
        $user = $this->getReference(LoadUserData::USER_NAME_2);

        self::assertEquals(
            $expectedCount,
            $this->getRepository()->countNavigationItems($url, $user, $user->getOrganization(), 'pinbar')
        );
    }

    public function countNavigationItemsWhenNoItemsDataProvider(): array
    {
        return [
            ['/sample-url', 0],
            ['', 0],
        ];
    }

    public function testCountNavigationItems(): void
    {
        $user = $this->getReference(LoadUserData::USER_NAME_2);
        $url = $this->getReference(NavigationItemData::NAVIGATION_ITEM_PINBAR_1)->getUrl();

        self::assertEquals(
            1,
            $this->getRepository()->countNavigationItems($url, $user, $user->getOrganization(), 'pinbar')
        );
    }

    public function testGetNavigationItems(): void
    {
        $user = $this->getReference(LoadUserData::USER_NAME_2);

        $pinbarItems = $this->getRepository()->getNavigationItems($user, $user->getOrganization(), 'pinbar');

        self::assertCount(3, $pinbarItems);

        self::assertEquals(
            $this->getReference(NavigationItemData::NAVIGATION_ITEM_PINBAR_1)->getUrl(),
            $pinbarItems[0]['url']
        );
        self::assertEquals('Configuration', $pinbarItems[0]['title_rendered_short']);
        self::assertEquals(
            $this->getReference(NavigationItemData::NAVIGATION_ITEM_PINBAR_3)->getUrl(),
            $pinbarItems[2]['url']
        );
        self::assertEquals('User', $pinbarItems[2]['title_rendered_short']);
    }

    /**
     * @dataProvider countPinbarTabDuplicatedTitlesDataProvider
     */
    public function testCountPinbarTabDuplicatedTitles(string $titleShort, int $expectedCount): void
    {
        $user = $this->getReference(LoadUserData::USER_NAME_2);

        self::assertEquals(
            $expectedCount,
            $this->getRepository()->countPinbarTabDuplicatedTitles($titleShort, $user, $user->getOrganization())
        );
    }

    public function countPinbarTabDuplicatedTitlesDataProvider(): array
    {
        return [
            ['Configuration', 1],
            ['Sample title', 0],
        ];
    }
}
