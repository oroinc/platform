<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateChangeEvent;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateWithScopeChangeEvent;
use Oro\Bundle\NavigationBundle\EventListener\MenuUpdateCacheFlusher;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Component\Testing\Unit\EntityTrait;

class MenuUpdateCacheFlusherTest extends \PHPUnit\Framework\TestCase
{
    const SCOPE_TYPE = 'custom_scope_type';
    use EntityTrait;

    /** @var MenuUpdateRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var MenuUpdateCacheFlusher */
    private $flusher;

    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(MenuUpdateRepository::class);
        $this->cache = $this->createMock(CacheProvider::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->flusher = new MenuUpdateCacheFlusher(
            $this->repository,
            $this->cache,
            $this->scopeManager,
            self::SCOPE_TYPE
        );
    }

    public function testOnMenuUpdateScopeChange()
    {
        $context = ['foo' => 'bar'];
        $event = new MenuUpdateChangeEvent('application_menu', $context);

        $scope = new Scope();
        $this->scopeManager->expects($this->once())
            ->method('find')
            ->with(self::SCOPE_TYPE, $context)
            ->willReturn($scope);

        $this->cache->expects($this->once())->method('delete');
        $this->repository->expects($this->once())
            ->method('findMenuUpdatesByScope')
            ->with('application_menu', $scope);

        $this->flusher->onMenuUpdateScopeChange($event);
    }

    public function testOnMenuUpdateWithScopeChange()
    {
        $scope = new Scope();
        $event = new MenuUpdateWithScopeChangeEvent('application_menu', $scope);

        $this->cache->expects($this->once())->method('delete');
        $this->repository->expects($this->once())
            ->method('findMenuUpdatesByScope')
            ->with('application_menu', $scope);

        $this->flusher->onMenuUpdateWithScopeChange($event);
    }
}
