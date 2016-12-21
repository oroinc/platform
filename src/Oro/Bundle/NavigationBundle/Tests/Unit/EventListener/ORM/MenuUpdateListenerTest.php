<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener\ORM;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\EventListener\ORM\MenuUpdateListener;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTrait;

class MenuUpdateListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var MenuUpdateRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $menuUpdateRepository;

    /** @var MenuUpdateListener */
    private $listener;

    protected function setUp()
    {
        $this->menuUpdateRepository = $this->getMock(MenuUpdateRepository::class, [], [], '', false);

        $this->listener = new MenuUpdateListener($this->menuUpdateRepository);
    }

    public function testPostPersist()
    {
        $scope = $this->getEntity(Scope::class);
        $update = $this->getEntity(MenuUpdate::class, ['menu' => 'test', 'scope' => $scope]);

        $args = $this->getLifecycleEventArgs();

        /** @var Cache|\PHPUnit_Framework_MockObject_MockObject $resultCacheImpl $resultCacheImpl */
        $resultCacheImpl = $args->getEntityManager()->getConfiguration()->getResultCacheImpl();
        $resultCacheImpl->expects($this->once())->method('delete');

        $this->menuUpdateRepository->expects($this->once())
            ->method('findMenuUpdatesByScope')
            ->with('test', $scope);

        $this->listener->postPersist($update, $args);
    }

    public function testPostUpdate()
    {
        $scope = $this->getEntity(Scope::class);
        $update = $this->getEntity(MenuUpdate::class, ['menu' => 'test', 'scope' => $scope]);

        $args = $this->getLifecycleEventArgs();

        /** @var Cache|\PHPUnit_Framework_MockObject_MockObject $resultCacheImpl $resultCacheImpl */
        $resultCacheImpl = $args->getEntityManager()->getConfiguration()->getResultCacheImpl();
        $resultCacheImpl->expects($this->once())->method('delete');

        $this->menuUpdateRepository->expects($this->once())
            ->method('findMenuUpdatesByScope')
            ->with('test', $scope);

        $this->listener->postUpdate($update, $args);
    }

    public function testPostRemove()
    {
        $scope = $this->getEntity(Scope::class);
        $update = $this->getEntity(MenuUpdate::class, ['menu' => 'test', 'scope' => $scope]);

        $args = $this->getLifecycleEventArgs();

        /** @var Cache|\PHPUnit_Framework_MockObject_MockObject $resultCacheImpl $resultCacheImpl */
        $resultCacheImpl = $args->getEntityManager()->getConfiguration()->getResultCacheImpl();
        $resultCacheImpl->expects($this->once())->method('delete');

        $this->menuUpdateRepository->expects($this->once())
            ->method('findMenuUpdatesByScope')
            ->with('test', $scope);

        $this->listener->postRemove($update, $args);
    }

    private function getLifecycleEventArgs()
    {
        /** @var Cache|\PHPUnit_Framework_MockObject_MockObject $resultCacheImpl */
        $resultCacheImpl = $this->getMock(Cache::class);

        /** @var Configuration|\PHPUnit_Framework_MockObject_MockObject $configuration */
        $configuration = $this->getMock(Configuration::class);
        $configuration->expects($this->any())->method('getResultCacheImpl')->willReturn($resultCacheImpl);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())->method('getConfiguration')->willReturn($configuration);

        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMock(LifecycleEventArgs::class, [], [], '', false);
        $args->expects($this->any())->method('getEntityManager')->willReturn($em);

        return $args;
    }
}
