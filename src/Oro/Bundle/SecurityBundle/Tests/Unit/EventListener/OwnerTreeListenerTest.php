<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\EventListener\OwnerTreeListener;

class OwnerTreeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provider
     *
     * @param string $supportedClass
     * @param array $inserts
     * @param array $updates
     * @param array $deletions
     * @param bool $isExpectedCache
     */
    public function testOnFlush($supportedClass, array $inserts, array $updates, array $deletions, $isExpectedCache)
    {
        $treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject $args */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())->method('get')->with('oro_security.ownership_tree_provider.chain')
            ->willReturn($treeProvider);
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($inserts));
        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue($updates));
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->will($this->returnValue($deletions));
        if ($isExpectedCache) {
            $treeProvider->expects($this->once())
                ->method('clear');
        } else {
            $treeProvider->expects($this->never())
                ->method('clear');
        }

        $treeListener = new OwnerTreeListener();
        $treeListener->setContainer($container);
        $treeListener->addSupportedClass($supportedClass);
        $treeListener->onFlush($args);
    }

    /**
     * @return array
     */
    public function provider()
    {
        return [
           'supported insert' => [
               'stdClass',
               [new \stdClass()],
               [new \DateTime()],
               [new \DateTime()],
               true
           ],
            'supported update' => [
                'stdClass',
                [new \DateTime()],
                [new \stdClass()],
                [new \DateTime()],
                true
            ],
            'supported delete' => [
                'stdClass',
                [new \DateTime()],
                [new \DateTime()],
                [new \stdClass()],
                true
            ],
            'unsupported class' => [
                'stdClass',
                [new \DateTime()],
                [new \DateTime()],
                [new \DateTime()],
                false
            ]
        ];
    }

    public function testOnFlushNoEntities()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->never())
            ->method($this->anything());

        $treeListener = new OwnerTreeListener();
        $treeListener->onFlush($args);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ContainerInterface not injected
     */
    public function testMissingContainer()
    {
        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([new \stdClass()]));
        $uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));
        $uow->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->will($this->returnValue([]));

        $treeListener = new OwnerTreeListener();
        $treeListener->addSupportedClass('stdClass');
        $treeListener->onFlush($args);
    }
}
