<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class MenuUpdateRepositoryTest extends WebTestCase
{
    /**
     * @var MenuUpdateRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([]);

        $this->loadFixtures([
            'Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData'
        ]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroNavigationBundle:MenuUpdate');
    }

    /**
     * @dataProvider getUpdatesProvider
     *
     * @param int $expectedCount
     * @param bool $useOrganizationScope
     * @param bool $useBusinessUnitScope
     * @param bool $useUserScope
     */
    public function testGetMenuUpdates(
        $expectedCount,
        $useOrganizationScope = false,
        $useBusinessUnitScope = false,
        $useUserScope = false
    ) {
        $updates = $this->repository->getMenuUpdates(
            MenuUpdateData::MENU,
            $useOrganizationScope ? $this->getReference(MenuUpdateData::ORGANIZATION) : null,
            $useBusinessUnitScope ? $this->getReference(MenuUpdateData::BUSINESS_UNIT) : null,
            $useUserScope ? $this->getReference(MenuUpdateData::USER) : null
        );
        $this->assertCount($expectedCount, $updates);
    }

    /**
     * @return array
     */
    public function getUpdatesProvider()
    {
        return [
            'global scope' => [
                'expectedCount' => 1,
            ],
            'organization scope' => [
                'expectedCount' => 2,
                'useOrganizationScope' => true,
            ],
            'business unit scope' => [
                'expectedCount' => 2,
                'useOrganizationScope' => false,
                'useBusinessUnitScope' => true,
                'useUserScope' => false,
            ],
            'user scope' => [
                'expectedCount' => 2,
                'useOrganizationScope' => false,
                'useBusinessUnitScope' => false,
                'useUserScope' => true,
            ],
            'all scopes' => [
                'expectedCount' => 4,
                'useOrganizationScope' => true,
                'useBusinessUnitScope' => true,
                'useUserScope' => true,
            ],
        ];
    }
}
