<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionChangesListener;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator;

use Oro\Component\DependencyInjection\ServiceLink;

class WorkflowDefinitionListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessConfigurationGenerator */
    protected $generator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink */
    protected $importLink;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink */
    protected $scheduleLink;

    /** @var WorkflowDefinitionChangesListener */
    protected $listener;

    /** @var ProcessConfigurator */
    protected $processConfigurator;

    protected function setUp()
    {
        $this->generator = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processConfigurator = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->importLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->scheduleLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WorkflowDefinitionListener($this->generator, $this->importLink, $this->scheduleLink);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->generator, $this->importLink);
    }

    public function testPostPersist()
    {
        $entity = new WorkflowDefinition();

        $this->assertImportExecuted($entity, ['definitions' => 'configuration']);

        $this->listener->postPersist($this->createEvent($entity));
    }

    public function testPostPersistWithUnsupportedEntity()
    {
        $this->assertImportNotExecuted();

        $this->listener->postPersist($this->createEvent(new \stdClass()));
    }

    public function testPostUpdate()
    {
        $entity = new WorkflowDefinition();

        $this->assertImportExecuted($entity, ['definitions' => ['process1' => 'configuration']]);
        $this->assertScheduleExecuted($entity, ['definitions' => ['process1' => 'configuration']]);

        $this->listener->postUpdate($this->createEvent($entity));
    }

    public function testPostUpdateWithUnsupportedEntity()
    {
        $this->assertImportNotExecuted();

        $this->listener->postUpdate($this->createEvent(new \stdClass()));
    }

    public function testPostRemove()
    {
        $this->listener->postRemove($this->createEvent(new \stdClass()));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Instance of Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator expected.
     */
    public function testProcessImportException()
    {
        $entity = new WorkflowDefinition();

        $this->generator->expects($this->once())
            ->method('generateForScheduledTransition')
            ->with($entity)
            ->willReturn(['definitions']);

        $this->importLink->expects($this->once())->method('getService')->willReturn(new \stdClass());

        $this->listener->postPersist($this->createEvent($entity));
    }

    /**
     * @param object $entity
     * @return LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEvent($entity)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $event */
        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getEntity')->willReturn($entity);

        return $event;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param array $configuration
     */
    protected function assertImportExecuted(WorkflowDefinition $workflowDefinition, array $configuration)
    {
        $this->generator->expects($this->once())
            ->method('generateForScheduledTransition')
            ->with($workflowDefinition)
            ->willReturn($configuration);

        /** @var ProcessConfigurator|\PHPUnit_Framework_MockObject_MockObject $import */
        $import = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator')
            ->disableOriginalConstructor()
            ->getMock();
        $import->expects($this->once())->method('configureProcesses')->with($configuration);

        $this->importLink->expects($this->once())
            ->method('getService')
            ->willReturn($import);
    }

    protected function assertImportNotExecuted()
    {
        $this->generator->expects($this->never())->method($this->anything());
        $this->importLink->expects($this->never())->method($this->anything());
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param array $configuration
     */
    protected function assertScheduleExecuted(WorkflowDefinition $workflowDefinition, array $configuration)
    {
        /** @var ProcessConfigurator|\PHPUnit_Framework_MockObject_MockObject $import */
        $scheduleService = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcesses')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ProcessConfigurator|\PHPUnit_Framework_MockObject_MockObject $import */
        $processDefinition = new ProcessDefinition();
        $processDefinition->setName('process1');
        $scheduleService->expects($this->once())->method('workflowRelated')->willReturn([$processDefinition]);

        $this->scheduleLink->expects($this->once())
            ->method('getService')
            ->willReturn($scheduleService);
    }
}
