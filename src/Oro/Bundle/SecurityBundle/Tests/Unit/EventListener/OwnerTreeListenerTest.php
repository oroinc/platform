<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
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
        $treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject $serviceLink */
        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($treeProvider));
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

        $treeListener = new OwnerTreeListener($serviceLink);
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
        /** @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject $serviceLink */
        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLink->expects($this->never())
            ->method($this->anything());

        /** @var OnFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->never())
            ->method($this->anything());

        $treeListener = new OwnerTreeListener($serviceLink);
        $treeListener->onFlush($args);
    }
}
