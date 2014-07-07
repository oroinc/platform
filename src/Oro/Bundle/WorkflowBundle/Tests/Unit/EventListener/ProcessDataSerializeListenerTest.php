<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\EventListener\ProcessDataSerializeListener;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

class ProcessDataSerializeListenerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS = 'Test\Class';

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
    public function testOnFlush($entities, $expected)
    {
        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($entities));
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue($entities));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));

        $this->assertAttributeEquals($expected, 'scheduledEntities', $this->listener);
    }

    public function onFlushProvider()
    {
        $stdClass   = new \stdClass();
        $processJob = new ProcessJob();

        return array(
            'string instead class' => array(
                'entities' => array('some class'),
                'expected' => null
            ),
            'invalid class' => array(
                'entities' => array($stdClass),
                'expected' => null
            ),
            'valid class' => array(
                'entities' => array($processJob),
                'expected' => array($processJob, $processJob)
            ),
            'several' => array(
                'entities' => array($processJob, $stdClass, 'str', $stdClass),
                'expected' => array($processJob, $processJob)
            ),
        );
    }

    public function testPostFlush()
    {
        $serializedData    = 'serializedData';
        $processDefinition = new ProcessDefinition();
        $processDefinition->setRelatedEntity(self::TEST_CLASS);

        $processTrigger = new ProcessTrigger();
        $processTrigger->setDefinition($processDefinition);

        $processData = new ProcessData();
        $processData->set('test', 'value');

        $processJob = new ProcessJob();
        $processJob->setProcessTrigger($processTrigger)
            ->setData($processData);

        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->at(0))
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue(array($processJob)));
        $unitOfWork->expects($this->at(1))
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue(array($processJob)));

        $entityId   = 1;
        $entityHash = ProcessJob::generateEntityHash(self::TEST_CLASS, $entityId);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($processJob->getData(), 'json', array('processJob' => $processJob))
            ->will(
                $this->returnCallback(
                    function () use ($processJob, $entityId, $serializedData) {
                        $processJob->setEntityId($entityId);
                        return $serializedData;
                    }
                )
            );

        $unitOfWork->expects($this->at(2))->method('propertyChanged')
            ->with($processJob, 'serializedData', null, $serializedData);
        $unitOfWork->expects($this->at(3))->method('propertyChanged')
            ->with($processJob, 'entityId', null, $entityId);
        $unitOfWork->expects($this->at(4))->method('propertyChanged')
            ->with($processJob, 'entityHash', null, $entityHash);

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
        $this->listener->postFlush(new PostFlushEventArgs($entityManager));
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
