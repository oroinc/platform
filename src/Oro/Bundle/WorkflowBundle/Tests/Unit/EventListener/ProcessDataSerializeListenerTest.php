<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

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
    public function testOnFlush($entity, $serializedData)
    {
        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue(array($entity)));
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue(array($entity)));

        if ($entity instanceof ProcessJob) {
            $entityId = 1;
            $entityHash = ProcessJob::generateEntityHash(self::TEST_CLASS, $entityId);

            $this->serializer->expects($this->once())
                ->method('serialize')
                ->with($entity->getData(), 'json', array('processJob' => $entity))
                ->will(
                    $this->returnCallback(
                        function () use ($entity, $entityId, $serializedData) {
                            $entity->setEntityId($entityId);
                            return $serializedData;
                        }
                    )
                );

            $unitOfWork->expects($this->at(1))->method('propertyChanged')
                ->with($entity, 'serializedData', null, $serializedData);
            $unitOfWork->expects($this->at(2))->method('propertyChanged')
                ->with($entity, 'entityId', null, $entityId);
            $unitOfWork->expects($this->at(3))->method('propertyChanged')
                ->with($entity, 'entityHash', null, $entityHash);
        } else {
            $this->serializer->expects($this->never())->method('serialize');
            $unitOfWork->expects($this->never())->method('propertyChanged');
        }

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));

        $onFlushArgs = new OnFlushEventArgs($entityManager);
        $this->listener->onFlush($onFlushArgs);
    }

    public function onFlushProvider()
    {
        $processDefinition = new ProcessDefinition();
        $processDefinition->setRelatedEntity(self::TEST_CLASS);

        $processTrigger = new ProcessTrigger();
        $processTrigger->setDefinition($processDefinition);

        $processData = new ProcessData();
        $processData->set('test', 'value');

        $processJob = new ProcessJob();
        $processJob->setProcessTrigger($processTrigger)
            ->setData($processData);

        return array(
            'string instead class' => array(
                'entity'         => 'some class',
                'serializedData' => 'serializedData'
            ),
            'invalid class' => array(
                'entity'         => new \stdClass(),
                'serializedData' => 'serializedData'
            ),
            'valid class' => array(
                'entity'         => $processJob,
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
