<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\PinbarTabData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;

class PinbarTabRepositoryTest extends WebTestCase
{
    use UserUtilityTrait;

    /** @var PinbarTabRepository */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->repository = self::getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(PinbarTab::class);

        $this->loadFixtures([
            PinbarTabData::class
        ]);
    }

    /**
     * @dataProvider countNavigationItemsDataProvider
     *
     * @param string $url
     * @param int $expectedCount
     */
    public function testCountNavigationItems(string $url, int $expectedCount): void
    {
        $manager = self::getContainer()->get('oro_entity.doctrine_helper')->getEntityManagerForClass(User::class);
        $user = $this->getFirstUser($manager);

        self::assertEquals(
            $expectedCount,
            $this->repository->countNavigationItems($url, $user, $user->getOrganization(), 'pinbar')
        );
    }

    /**
     * @return array
     */
    public function countNavigationItemsDataProvider(): array
    {
        return [
            ['/sample-url', 0],
            ['/admin/user', 1],
            ['', 0],
        ];
    }

    public function testGetNavigationItems(): void
    {
        $manager = self::getContainer()->get('oro_entity.doctrine_helper')->getEntityManagerForClass(User::class);
        $user = $this->getFirstUser($manager);

        $pinbarItems = $this->repository->getNavigationItems($user, $user->getOrganization(), 'pinbar');

        self::assertCount(3, $pinbarItems);
        self::assertEquals('/admin/config/system', $pinbarItems[0]['url']);
        self::assertEquals('Configuration', $pinbarItems[0]['title_rendered_short']);
        self::assertEquals('/admin/user', $pinbarItems[2]['url']);
        self::assertEquals('User', $pinbarItems[2]['title_rendered_short']);
    }

    /**
     * @dataProvider countPinbarTabDuplicatedTitlesDataProvider
     *
     * @param string $titleShort
     * @param int $expectedCount
     */
    public function testCountPinbarTabDuplicatedTitles(string $titleShort, int $expectedCount): void
    {
        $manager = self::getContainer()->get('oro_entity.doctrine_helper')->getEntityManagerForClass(User::class);
        $user = $this->getFirstUser($manager);

        self::assertEquals(
            $expectedCount,
            $this->repository->countPinbarTabDuplicatedTitles($titleShort, $user, $user->getOrganization())
        );
    }

    /**
     * @return array
     */
    public function countPinbarTabDuplicatedTitlesDataProvider(): array
    {
        return [
            ['Configuration', 1],
            ['Sample title', 0],
        ];
    }
}
