<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\EventListener\ProcessDataSerializeListener;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

class ProcessDataSerializeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializer;

    /**
     * @var ProcessDataSerializeListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->serializer = $this->getMockForAbstractClass('Symfony\Component\Serializer\SerializerInterface');
        $this->listener = new ProcessDataSerializeListener($this->serializer);
    }

    /**
     * @dataProvider onFlushProvider
     */
    public function testOnFlush($entityCount, $entity, $serializedData)
    {
        $this->markTestIncomplete('Should be fixed in scope of CRM-763');

        $processData = new ProcessData();
        $entities = array_fill(0, $entityCount, $entity);

        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($entities));
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue($entities));
        $unitOfWork->expects($this->any())
            ->method('propertyChanged')
            ->will($this->returnValue($entity));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));

        if ($entity instanceof ProcessJob) {
            $this->serializer->expects($this->any())
                ->method('serialize')
                ->with($processData, 'json', array('processJob' => $entity))
                ->will($this->returnValue($serializedData));
        } else {
            $this->serializer->expects($this->never())->method('serialize');
        }

        $onFlushArgs = new OnFlushEventArgs($entityManager);
        $this->listener->onFlush($onFlushArgs);
    }

    public function onFlushProvider()
    {
        return array(
            'string instead class' => array(
                'callCount'      => 3,
                'entity'         => 'some class',
                'serializedData' => 'serializedData'
            ),
            'invalid class' => array(
                'callCount'      => 3,
                'entity'         => new \stdClass(),
                'serializedData' => 'serializedData'
            ),
            'valid class' => array(
                'callCount'      => 3,
                'entity'         => new ProcessJob(),
                'serializedData' => 'serializedData'
            ),
        );
    }

    /**
     * @dataProvider postLoadProvider
     */
    public function testPostLoad($entity)
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $lifecycleEventArgs = new LifecycleEventArgs($entity, $entityManager);
        $this->listener->postLoad($lifecycleEventArgs);
    }

    public function postLoadProvider()
    {
        return array(
            'string instead class' => array(
                'entity' => 'some class',
            ),
            'invalid class' => array(
                'entity' => new \stdClass(),
            ),
            'valid class' => array(
                'entity' => $this->getMockProcessJob(),
            ),
        );
    }

    protected function getMockProcessJob()
    {
        $processJob = $this->getMock('Oro\Bundle\WorkflowBundle\Entity\ProcessJob');
        $processJob->expects($this->once())
            ->method('setSerializer')
            ->will($this->returnSelf());
        return $processJob;
    }
}
