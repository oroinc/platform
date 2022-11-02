<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProvider;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\UserBundle\Entity\User;

class MenuUpdateProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var MenuUpdateManager|\PHPUnit\Framework\MockObject\MockObject */
    private $menuUpdateManager;

    /** @var ItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $menuItem;

    /** @var MenuUpdateProvider */
    private $menuUpdateProvider;

    protected function setUp(): void
    {
        $this->menuItem = $this->createMock(ItemInterface::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->menuUpdateManager = $this->createMock(MenuUpdateManager::class);

        $this->menuUpdateProvider = new MenuUpdateProvider(
            $this->scopeManager,
            $this->menuUpdateManager
        );
    }

    public function testEmptyMenuUpdates()
    {
        $this->menuItem->expects(self::exactly(2))
            ->method('getExtra')
            ->with('scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE)
            ->willReturn(ConfigurationBuilder::DEFAULT_SCOPE_TYPE);
        $this->menuUpdateManager->expects(self::exactly(2))
            ->method('getScopeType')
            ->willReturn('test_scope');
        self::assertEmpty($this->menuUpdateProvider->getMenuUpdatesForMenuItem($this->menuItem));
        self::assertEmpty($this->menuUpdateProvider->getMenuUpdatesForMenuItem($this->menuItem));
    }

    public function testGetMenuUpdatesCalledMoreThanOnce()
    {
        $options = [MenuUpdateProvider::SCOPE_CONTEXT_OPTION => ['scopeAttribute' => new \stdClass()]];
        $this->menuItem->expects(self::exactly(2))
            ->method('getExtra')
            ->with('scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE)
            ->willReturn('test_scope');
        $this->menuItem->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('my_menu');

        $this->menuUpdateManager->expects(self::exactly(2))
            ->method('getScopeType')
            ->willReturn('test_scope');

        $scopeIds = [1];
        $this->scopeManager->expects(self::once())
            ->method('findRelatedScopeIdsWithPriority')
            ->with('test_scope', ['scopeAttribute' => new \stdClass()])
            ->willReturn($scopeIds);

        $repository = $this->createMock(MenuUpdateRepository::class);

        $this->menuUpdateManager->expects(self::any())
            ->method('getRepository')
            ->willReturn($repository);

        $updates = [new MenuUpdate()];
        $repository->expects(self::exactly(2))
            ->method('findMenuUpdatesByScopeIds')
            ->with('my_menu', $scopeIds)
            ->willReturn($updates);
        $repository->expects(self::once())
            ->method('getUsedScopesByMenu')
            ->willReturn(['my_menu' => [1]]);

        $this->assertSame($updates, $this->menuUpdateProvider->getMenuUpdatesForMenuItem($this->menuItem, $options));
        $this->assertSame($updates, $this->menuUpdateProvider->getMenuUpdatesForMenuItem($this->menuItem, $options));
    }

    public function testGetMenuUpdatesWithDifferentScopeOptions()
    {
        $user = $this->createMock(User::class);
        $options1 = [MenuUpdateProvider::SCOPE_CONTEXT_OPTION => ['scopeAttribute' => new \stdClass()]];
        $options2 = [MenuUpdateProvider::SCOPE_CONTEXT_OPTION => ['scopeAttribute' => $user]];
        $this->menuItem->expects(self::exactly(2))
            ->method('getExtra')
            ->with('scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE)
            ->willReturn('test_scope');
        $this->menuItem->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('my_menu');

        $this->menuUpdateManager->expects(self::exactly(2))
            ->method('getScopeType')
            ->willReturn('test_scope');

        $scopeIds1 = [1];
        $scopeIds2 = [2];
        $this->scopeManager->expects(self::exactly(2))
            ->method('findRelatedScopeIdsWithPriority')
            ->withConsecutive(
                ['test_scope', ['scopeAttribute' => new \stdClass()]],
                ['test_scope', ['scopeAttribute' => $user]]
            )
            ->willReturnOnConsecutiveCalls(
                $scopeIds1,
                $scopeIds2
            );

        $repository = $this->createMock(MenuUpdateRepository::class);

        $this->menuUpdateManager->expects(self::any())
            ->method('getRepository')
            ->willReturn($repository);

        $updates = [new MenuUpdate()];
        $repository->expects(self::exactly(2))
            ->method('findMenuUpdatesByScopeIds')
            ->withConsecutive(
                ['my_menu', $scopeIds1],
                ['my_menu', $scopeIds2]
            )
            ->willReturn($updates);
        $repository->expects(self::once())
            ->method('getUsedScopesByMenu')
            ->willReturn(['my_menu' => [1, 2]]);

        $this->assertSame($updates, $this->menuUpdateProvider->getMenuUpdatesForMenuItem($this->menuItem, $options1));
        $this->assertSame($updates, $this->menuUpdateProvider->getMenuUpdatesForMenuItem($this->menuItem, $options2));
    }

    public function testGetMenuUpdatesForMenuWithLimitedScopes()
    {
        $options = [MenuUpdateProvider::SCOPE_CONTEXT_OPTION => ['scopeAttribute' => new \stdClass()]];
        $this->menuItem->expects(self::any())
            ->method('getExtra')
            ->with('scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE)
            ->willReturn('test_scope');
        $this->menuItem->expects(self::any())
            ->method('getName')
            ->willReturn('my_menu');

        $this->menuUpdateManager->expects(self::any())
            ->method('getScopeType')
            ->willReturn('test_scope');

        $scopeIds = [1, 2];
        $this->scopeManager->expects(self::once())
            ->method('findRelatedScopeIdsWithPriority')
            ->with('test_scope', ['scopeAttribute' => new \stdClass()])
            ->willReturn($scopeIds);

        $repository = $this->createMock(MenuUpdateRepository::class);

        $this->menuUpdateManager->expects(self::any())
            ->method('getRepository')
            ->willReturn($repository);

        $updates = [new MenuUpdate()];
        $repository->expects(self::once())
            ->method('getUsedScopesByMenu')
            ->willReturn(['my_menu' => [1]]);
        $repository->expects(self::once())
            ->method('findMenuUpdatesByScopeIds')
            ->with('my_menu', [1])
            ->willReturn($updates);

        $this->assertSame($updates, $this->menuUpdateProvider->getMenuUpdatesForMenuItem($this->menuItem, $options));
    }
}
