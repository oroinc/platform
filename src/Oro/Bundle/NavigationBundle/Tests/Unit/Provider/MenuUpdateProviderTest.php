<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProvider;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class MenuUpdateProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeManager;

    /**
     * @var MenuUpdateManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $menuUpdateManager;

    /**
     * @var ItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $menuItem;

    /**
     * @var MenuUpdateProvider
     */
    private $menuUpdateProvider;

    protected function setUp()
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
        $this->menuItem->expects(static::once())
            ->method('getExtra')
            ->with('scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE)
            ->willReturn(ConfigurationBuilder::DEFAULT_SCOPE_TYPE);
        $this->menuUpdateManager->expects(static::once())
            ->method('getScopeType')
            ->willReturn('test_scope');
        static::assertEmpty($this->menuUpdateProvider->getMenuUpdatesForMenuItem($this->menuItem));
    }

    public function testGetMenuUpdates()
    {
        $options = [MenuUpdateProvider::SCOPE_CONTEXT_OPTION => ['scopeAttribute' => new \stdClass()]];
        $this->menuItem->expects(static::once())
            ->method('getExtra')
            ->with('scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE)
            ->willReturn('test_scope');

        $this->menuUpdateManager->expects(static::once())
            ->method('getScopeType')
            ->willReturn('test_scope');

        $this->scopeManager->expects(static::once())
            ->method('findRelatedScopeIdsWithPriority')
            ->with('test_scope', $this->equalTo(['scopeAttribute' => new \stdClass()]))
            ->willReturn([]);

        $repository = $this->createMock(MenuUpdateRepository::class);

        $this->menuUpdateManager->expects(static::once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects(static::once())
            ->method('findMenuUpdatesByScopeIds')
            ->with(null, [])
            ->willReturn([]);

        $this->menuUpdateProvider->getMenuUpdatesForMenuItem($this->menuItem, $options);
    }
}
