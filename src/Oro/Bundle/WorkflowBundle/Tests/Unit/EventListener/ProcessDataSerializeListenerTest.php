<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\EventListener\ProcessDataSerializeListener;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\TestUtils\Mocks\ServiceLink;

class ProcessDataSerializeListenerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_CLASS = 'Test\Class';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $serializer;

    /**
     * @var ProcessDataSerializeListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->serializer = $this->getMockForAbstractClass('Symfony\Component\Serializer\SerializerInterface');
        $this->listener = new ProcessDataSerializeListener(new ServiceLink($this->serializer));
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
        $processJob->getData()->set('key', 'value');

        return array(
            'string instead class' => array(
                'entities' => array('some class'),
                'expected' => array()
            ),
            'invalid class' => array(
                'entities' => array($stdClass),
                'expected' => array()
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

        $this->serializer->expects($this->exactly(2))
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

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));
        $entityManager->expects($this->once())
            ->method('flush');

        $this->listener->onFlush(new OnFlushEventArgs($entityManager));
        $this->listener->postFlush(new PostFlushEventArgs($entityManager));

        $this->assertEquals($serializedData, $processJob->getSerializedData());
        $this->assertEquals($entityId, $processJob->getEntityId());
        $this->assertEquals($entityHash, $processJob->getEntityHash());
        $this->assertFalse($processJob->getData()->isModified());
    }

    public function testPostLoad()
    {
        $entity = $this->createMock(ProcessJob::class);

        $entity->expects(self::once())
            ->method('setSerializer')
            ->with(self::identicalTo($this->serializer), 'json');

        $lifecycleEventArgs = new LifecycleEventArgs($entity, $this->createMock(EntityManager::class));
        $this->listener->postLoad($entity, $lifecycleEventArgs);
    }
}
