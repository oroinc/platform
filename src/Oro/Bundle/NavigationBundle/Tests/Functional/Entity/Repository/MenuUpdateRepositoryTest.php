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

    public function testGetUsedScopesByMenu(): void
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
        self::assertEqualsCanonicalizing($expected, $this->getRepository()->getUsedScopesByMenu());
    }

    /**
     * @dataProvider findMenuUpdatesByScopeReferencesDataProvider
     */
    public function testFindMenuUpdatesByScopeIds(
        string $menuName,
        array $scopeReferences,
        array $expectedMenuUpdateReferences
    ): void {
        $scopeIds = $this->getScopeIdsByReferences($scopeReferences);
        /** @var MenuUpdate[] $actualMenuUpdates */
        $actualMenuUpdates = $this->getRepository()->findMenuUpdatesByScopeIds($menuName, $scopeIds);
        self::assertCount(count($expectedMenuUpdateReferences), $actualMenuUpdates);
        $menuUpdateIds = [];
        foreach ($actualMenuUpdates as $menuUpdate) {
            $menuUpdateIds[] = $menuUpdate->getId();
        }
        foreach ($expectedMenuUpdateReferences as $menuUpdateReference) {
            self::assertContains($this->getReference($menuUpdateReference)->getId(), $menuUpdateIds);
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
    ): void {
        $scope = $this->getReference($scopeReference);
        $actualMenuUpdates = $this->getRepository()->findMenuUpdatesByScope($menu, $scope);
        self::assertCount(count($expectedMenuUpdateReferences), $actualMenuUpdates);
        $menuUpdateIds = [];
        foreach ($actualMenuUpdates as $menuUpdate) {
            $menuUpdateIds[] = $menuUpdate->getId();
        }
        foreach ($expectedMenuUpdateReferences as $menuUpdateReference) {
            self::assertContains($this->getReference($menuUpdateReference)->getId(), $menuUpdateIds);
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

    public function testUpdateDependentMenuUpdateUri(): void
    {
        /** @var MenuUpdate $globalMenuUpdate */
        $globalMenuUpdate = $this->getReference('test_menu_item1_global');
        /** @var MenuUpdate $userMenuUpdate */
        $userMenuUpdate = $this->getReference('test_menu_item1_user');
        $this->getRepository()->updateDependentMenuUpdates($globalMenuUpdate);

        self::assertEquals($globalMenuUpdate->getUri(), $userMenuUpdate->getUri());
    }

    public function testGetDependentMenuUpdateScopes(): void
    {
        /** @var MenuUpdate $globalMenuUpdate */
        $globalMenuUpdate = $this->getReference('test_menu_item1_global');
        $scopes = $this->getRepository()->getDependentMenuUpdateScopes($globalMenuUpdate);
        self::assertCount(1, $scopes);
        /** @var Scope $expectedScope */
        $expectedScope = $this->getReference(LoadScopeUserData::SIMPLE_USER_SCOPE);

        self::assertEquals($expectedScope->getId(), $scopes[0]->getId());
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

    public function testFindManyWithoutKeys(): void
    {
        self::assertEmpty(
            $this->getRepository()->findMany(
                'application_menu',
                $this->getReference(LoadScopeData::DEFAULT_SCOPE)->getId(),
                []
            )
        );
    }

    public function testFindManyWhenNoResult(): void
    {
        self::assertEmpty(
            $this->getRepository()->findMany(
                'application_menu',
                $this->getReference(LoadScopeData::DEFAULT_SCOPE)->getId(),
                ['sample_key']
            )
        );
    }

    public function testFindManyWithKeys(): void
    {
        $menuUpdate2 = $this->getReference(MenuUpdateData::MENU_UPDATE_2);
        $menuUpdate21 = $this->getReference(MenuUpdateData::MENU_UPDATE_2_1);
        $menuUpdate211 = $this->getReference(MenuUpdateData::MENU_UPDATE_2_1_1);

        self::assertEqualsCanonicalizing(
            [
                $menuUpdate2->getKey() => $menuUpdate2,
                $menuUpdate21->getKey() => $menuUpdate21,
                $menuUpdate211->getKey() => $menuUpdate211,
            ],
            $this->getRepository()->findMany(
                'application_menu',
                $this->getReference(LoadScopeData::DEFAULT_SCOPE)->getId(),
                [
                    $menuUpdate2->getKey(),
                    $menuUpdate21->getKey(),
                    $menuUpdate211->getKey(),
                ]
            )
        );
    }
}
