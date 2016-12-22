<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener\ORM;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\EventListener\ORM\MenuUpdateListener;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTrait;

class MenuUpdateListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $container;

    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $cache;

    /** @var MenuUpdateListener */
    private $listener;

    protected function setUp()
    {
        $this->cache = $this->getMock(CacheProvider::class);

        $this->container = $this->getMock(ContainerInterface::class);
        $this->container->expects($this->any())
            ->method('get')
            ->with(MenuUpdateListener::MENU_CACHE_SERVICE_ID)
            ->willReturn($this->cache);

        $this->listener = new MenuUpdateListener();
        $this->listener->setContainer($this->container);
    }

    public function testPostPersist()
    {
        $scope = $this->getEntity(Scope::class);
        /** @var MenuUpdate $update */
        $update = $this->getEntity(MenuUpdate::class, ['menu' => 'test', 'scope' => $scope]);

        $repository = $this->getMock(MenuUpdateRepository::class, [], [], '', false);
        $repository->expects($this->once())->method('findMenuUpdatesByScope')->with('test', $scope);

        $classMetadata = $this->getMock(ClassMetadata::class, [], [], '', false);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())->method('getRepository')->willReturn($repository);
        $em->expects($this->any())->method('getClassMetadata')->willReturn($classMetadata);

        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMock(LifecycleEventArgs::class, [], [], '', false);
        $args->expects($this->any())->method('getEntityManager')->willReturn($em);

        $this->listener->postPersist($update, $args);
    }

    public function testPostUpdate()
    {
        $scope = $this->getEntity(Scope::class);
        /** @var MenuUpdate $update */
        $update = $this->getEntity(MenuUpdate::class, ['menu' => 'test', 'scope' => $scope]);

        $repository = $this->getMock(MenuUpdateRepository::class, [], [], '', false);
        $repository->expects($this->once())->method('findMenuUpdatesByScope')->with('test', $scope);

        $classMetadata = $this->getMock(ClassMetadata::class, [], [], '', false);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())->method('getRepository')->willReturn($repository);
        $em->expects($this->any())->method('getClassMetadata')->willReturn($classMetadata);

        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMock(LifecycleEventArgs::class, [], [], '', false);
        $args->expects($this->any())->method('getEntityManager')->willReturn($em);

        $this->listener->postUpdate($update, $args);
    }

    public function testPostRemove()
    {
        $scope = $this->getEntity(Scope::class);
        /** @var MenuUpdate $update */
        $update = $this->getEntity(MenuUpdate::class, ['menu' => 'test', 'scope' => $scope]);

        $repository = $this->getMock(MenuUpdateRepository::class, [], [], '', false);
        $repository->expects($this->once())->method('findMenuUpdatesByScope')->with('test', $scope);

        $classMetadata = $this->getMock(ClassMetadata::class, [], [], '', false);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->any())->method('getRepository')->willReturn($repository);
        $em->expects($this->any())->method('getClassMetadata')->willReturn($classMetadata);

        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMock(LifecycleEventArgs::class, [], [], '', false);
        $args->expects($this->any())->method('getEntityManager')->willReturn($em);

        $this->listener->postRemove($update, $args);
    }
}
