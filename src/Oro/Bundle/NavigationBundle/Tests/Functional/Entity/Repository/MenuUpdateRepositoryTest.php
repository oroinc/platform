<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;
use Oro\Bundle\ScopeBundle\Tests\DataFixtures\LoadScopeData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadScopeUserData;

class MenuUpdateRepositoryTest extends WebTestCase
{
    use UserUtilityTrait;

    /** @var  MenuUpdateRepository */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->repository = $this
            ->getContainer()
            ->get('doctrine')
            ->getManagerForClass(MenuUpdate::class)
            ->getRepository(MenuUpdate::class);

        $this->loadFixtures(
            [
                MenuUpdateData::class
            ]
        );
    }

    /**
     * @dataProvider findMenuUpdatesByscopeReferencesDataProvider
     * @param string $menuName
     * @param array  $scopeReferences
     * @param array  $expectedMenuUpdateReferences
     */
    public function testFindMenuUpdatesByScopeIds(
        $menuName,
        array $scopeReferences,
        array $expectedMenuUpdateReferences
    ) {
        $scopeIds = $this->getScopeIdsByReferences($scopeReferences);
        /** @var MenuUpdate[] $actualMenuUpdates */
        $actualMenuUpdates = $this->repository->findMenuUpdatesByScopeIds($menuName, $scopeIds);
        $this->assertCount(count($expectedMenuUpdateReferences), $actualMenuUpdates);
        $menuUpdateIds = [];
        foreach ($actualMenuUpdates as $menuUpdate) {
            $menuUpdateIds[] = $menuUpdate->getId();
        }
        foreach ($expectedMenuUpdateReferences as $menuUpdateReference) {
            $this->assertContains($this->getReference($menuUpdateReference)->getId(), $menuUpdateIds);
        }
    }

    /**
     * @return array
     */
    public function findMenuUpdatesByscopeReferencesDataProvider()
    {
        return [
            'global scope only' => [
                'menu' => 'application_menu',
                'scopeReferences' => [LoadScopeData::DEFAULT_SCOPE],
                'expectedMenuUpdateReferences' => [
                    MenuUpdateData::MENU_UPDATE_1,
                    MenuUpdateData::MENU_UPDATE_1_1,
                    MenuUpdateData::MENU_UPDATE_2,
                    MenuUpdateData::MENU_UPDATE_2_1,
                    MenuUpdateData::MENU_UPDATE_2_1_1,
                ]
            ],
            'global and user scopes' => [
                'menu' => 'application_menu',
                'scopeReferences' => [LoadScopeData::DEFAULT_SCOPE, LoadScopeUserData::SIMPLE_USER_SCOPE],
                'expectedMenuUpdateReferences' => [
                    MenuUpdateData::MENU_UPDATE_1,
                    MenuUpdateData::MENU_UPDATE_1_1,
                    MenuUpdateData::MENU_UPDATE_2,
                    MenuUpdateData::MENU_UPDATE_2_1,
                    MenuUpdateData::MENU_UPDATE_2_1_1,
                    MenuUpdateData::MENU_UPDATE_3,
                    MenuUpdateData::MENU_UPDATE_3_1,
                ]
            ],
        ];
    }

    /**
     * @dataProvider findMenuUpdatesByScopeDataProvider
     *
     * @param string $menu
     * @param string $scopeReference
     * @param array  $expectedMenuUpdateReferences
     */
    public function testFindMenuUpdatesByScope($menu, $scopeReference, $expectedMenuUpdateReferences)
    {
        $scope = $this->getReference($scopeReference);
        $actualMenuUpdates = $this->repository->findMenuUpdatesByScope($menu, $scope);
        $this->assertCount(count($expectedMenuUpdateReferences), $actualMenuUpdates);
        $menuUpdateIds = [];
        foreach ($actualMenuUpdates as $menuUpdate) {
            $menuUpdateIds[] = $menuUpdate->getId();
        }
        foreach ($expectedMenuUpdateReferences as $menuUpdateReference) {
            $this->assertContains($this->getReference($menuUpdateReference)->getId(), $menuUpdateIds);
        }
    }

    /**
     * @return array
     */
    public function findMenuUpdatesByScopeDataProvider()
    {
        return [
            'global scope' => [
                'menu' => 'application_menu',
                'scopeReference' => LoadScopeData::DEFAULT_SCOPE,
                'expectedMenuUpdateReferences' => [
                    MenuUpdateData::MENU_UPDATE_1,
                    MenuUpdateData::MENU_UPDATE_1_1,
                    MenuUpdateData::MENU_UPDATE_2,
                    MenuUpdateData::MENU_UPDATE_2_1,
                    MenuUpdateData::MENU_UPDATE_2_1_1,
                ]
            ],
            'user scope' => [
                'menu' => 'application_menu',
                'scopeReference' => LoadScopeUserData::SIMPLE_USER_SCOPE,
                'expectedMenuUpdateReferences' => [
                    MenuUpdateData::MENU_UPDATE_3,
                    MenuUpdateData::MENU_UPDATE_3_1,
                ]
            ],
        ];
    }

    /**
     * @param array $scopeReferences
     * @return array
     */
    protected function getScopeIdsByReferences(array $scopeReferences)
    {
        $scopeIds = array_map(
            function ($reference) {
                return $this->getReference($reference)->getId();
            },
            $scopeReferences
        );

        return $scopeIds;
    }
}
