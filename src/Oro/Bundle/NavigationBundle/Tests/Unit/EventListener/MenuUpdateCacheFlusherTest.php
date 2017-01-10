<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Event\MenuUpdateScopeChangeEvent;
use Oro\Bundle\NavigationBundle\EventListener\MenuUpdateCacheFlusher;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTrait;

class MenuUpdateCacheFlusherTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var MenuUpdateRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $cache;

    /** @var MenuUpdateCacheFlusher */
    private $flusher;

    protected function setUp()
    {
        $this->repository = $this->createMock(MenuUpdateRepository::class);
        $this->cache = $this->createMock(CacheProvider::class);
        $this->flusher = new MenuUpdateCacheFlusher($this->repository, $this->cache);
    }

    public function testOnMenuUpdateScopeChange()
    {
        $scope = $this->getEntity(Scope::class);

        /** @var MenuUpdateScopeChangeEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(MenuUpdateScopeChangeEvent::class);
        $event->expects($this->any())->method('getMenuName')->willReturn('application_menu');
        $event->expects($this->any())->method('getScope')->willReturn($scope);

        $this->cache->expects($this->once())->method('delete');
        $this->repository->expects($this->once())
            ->method('findMenuUpdatesByScope')
            ->with('application_menu', $scope);

        $this->flusher->onMenuUpdateScopeChange($event);
    }
}
