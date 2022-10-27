<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadScopeUserData;

/**
 * @dbIsolationPerTest
 */
class MenuUpdateRepositoryTest extends WebTestCase
{
    use UserUtilityTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([MenuUpdateData::class]);
    }

    private function getRepository(): MenuUpdateRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(MenuUpdate::class);
    }

    public function testGetUsedScopesByMenu()
    {
        $expected = [
            'test_menu' => [
                $this->getReference(LoadScopeData::DEFAULT_SCOPE)->getId(),
                $this->getReference(LoadScopeUserData::SIMPLE_USER_SCOPE)->getId(),
            ],
            'application_menu' => [
                $this->getReference(LoadScopeData::DEFAULT_SCOPE)->getId(),
                $this->getReference(LoadScopeUserData::SIMPLE_USER_SCOPE)->getId(),
            ]
        ];
        $this->assertEqualsCanonicalizing($expected, $this->getRepository()->getUsedScopesByMenu());
    }

    /**
     * @dataProvider findMenuUpdatesByScopeReferencesDataProvider
     */
    public function testFindMenuUpdatesByScopeIds(
        string $menuName,
        array $scopeReferences,
        array $expectedMenuUpdateReferences
    ) {
        $scopeIds = $this->getScopeIdsByReferences($scopeReferences);
        /** @var MenuUpdate[] $actualMenuUpdates */
        $actualMenuUpdates = $this->getRepository()->findMenuUpdatesByScopeIds($menuName, $scopeIds);
        $this->assertCount(count($expectedMenuUpdateReferences), $actualMenuUpdates);
        $menuUpdateIds = [];
        foreach ($actualMenuUpdates as $menuUpdate) {
            $menuUpdateIds[] = $menuUpdate->getId();
        }
        foreach ($expectedMenuUpdateReferences as $menuUpdateReference) {
            $this->assertContains($this->getReference($menuUpdateReference)->getId(), $menuUpdateIds);
        }
    }

    public function findMenuUpdatesByScopeReferencesDataProvider(): array
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
     */
    public function testFindMenuUpdatesByScope(
        string $menu,
        string $scopeReference,
        array $expectedMenuUpdateReferences
    ) {
        $scope = $this->getReference($scopeReference);
        $actualMenuUpdates = $this->getRepository()->findMenuUpdatesByScope($menu, $scope);
        $this->assertCount(count($expectedMenuUpdateReferences), $actualMenuUpdates);
        $menuUpdateIds = [];
        foreach ($actualMenuUpdates as $menuUpdate) {
            $menuUpdateIds[] = $menuUpdate->getId();
        }
        foreach ($expectedMenuUpdateReferences as $menuUpdateReference) {
            $this->assertContains($this->getReference($menuUpdateReference)->getId(), $menuUpdateIds);
        }
    }

    public function findMenuUpdatesByScopeDataProvider(): array
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

    public function testUpdateDependentMenuUpdateUri()
    {
        /** @var MenuUpdate $globalMenuUpdate */
        $globalMenuUpdate = $this->getReference('test_menu_item1_global');
        /** @var MenuUpdate $userMenuUpdate */
        $userMenuUpdate = $this->getReference('test_menu_item1_user');
        $this->getRepository()->updateDependentMenuUpdates($globalMenuUpdate);

        $this->assertEquals($globalMenuUpdate->getUri(), $userMenuUpdate->getUri());
    }

    public function testGetDependentMenuUpdateScopes()
    {
        /** @var MenuUpdate $globalMenuUpdate */
        $globalMenuUpdate = $this->getReference('test_menu_item1_global');
        $scopes = $this->getRepository()->getDependentMenuUpdateScopes($globalMenuUpdate);
        $this->assertCount(1, $scopes);
        /** @var Scope $expectedScope */
        $expectedScope = $this->getReference(LoadScopeUserData::SIMPLE_USER_SCOPE);

        $this->assertEquals($expectedScope->getId(), $scopes[0]->getId());
    }

    private function getScopeIdsByReferences(array $scopeReferences): array
    {
        return array_map(
            function ($reference) {
                return $this->getReference($reference)->getId();
            },
            $scopeReferences
        );
    }
}
