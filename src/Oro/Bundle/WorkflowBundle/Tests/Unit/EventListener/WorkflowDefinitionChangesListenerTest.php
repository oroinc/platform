<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionChangesListener;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator;

use Oro\Component\DependencyInjection\ServiceLink;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class WorkflowDefinitionChangesListenerTest extends \PHPUnit_Framework_TestCase
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

        $this->listener = new WorkflowDefinitionChangesListener(
            $this->generator,
            $this->importLink,
            $this->scheduleLink
        );
    }

    protected function tearDown()
    {
        unset($this->listener, $this->generator, $this->importLink);
    }

    public function testGenerateProcessConfigurations()
    {
        $workflowDefinition = new WorkflowDefinition();

        $this->generator->expects($this->once())
            ->method('generateForScheduledTransition')
            ->with($workflowDefinition);

        $this->listener->generateProcessConfigurations($this->createEvent($workflowDefinition));
    }

    public function testWorkflowCreated()
    {
        $entity = new WorkflowDefinition();
        $entity->setName('test_workflow');

        $this->assertImportExecuted($entity, ['test_workflow' => ['definitions' => ['configuration']]]);

        $this->listener->workflowCreated($this->createEvent($entity));
    }

    public function testWorkflowUpdated()
    {
        $entity = new WorkflowDefinition();
        $entity->setName('test_workflow');
        $this->assertImportExecuted($entity, ['test_workflow' => ['definitions' => ['configuration']]]);
        $this->assertScheduleExecuted();

        $this->listener->workflowUpdated($this->createEvent($entity));
    }

    public function testWorkflowDeleted()
    {
        $entity = new WorkflowDefinition();
        $entity->setName('test_workflow');
        $this->assertImportExecuted($entity, ['test_workflow' => ['definitions' => ['configuration']]]);
        $this->assertScheduleExecuted();

        $this->listener->workflowDeleted($this->createEvent(new WorkflowDefinition()));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Instance of Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator expected.
     */
    public function testProcessImportLinkException()
    {
        $entity = new WorkflowDefinition();
        $entity->setName('test_workflow');

        $this->setGeneratedConfigurations(['test_workflow' => ['definitions' => ['configuration']]]);

        $this->importLink->expects($this->once())->method('getService')->willReturn(new \stdClass());

        $this->listener->workflowCreated($this->createEvent($entity));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Instance of
     * Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcesses expected.
     */
    public function testScheduleLinkException()
    {
        $entity = new WorkflowDefinition();
        $entity->setName('test_workflow');
        $this->assertImportExecuted($entity, ['test_workflow' => ['definitions' => ['configuration']]]);

        $this->scheduleLink->expects($this->once())->method('getService')->willReturn(new \stdClass());

        $this->listener->workflowUpdated($this->createEvent($entity));
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                WorkflowEvents::WORKFLOW_BEFORE_CREATE => 'generateProcessConfigurations',
                WorkflowEvents::WORKFLOW_BEFORE_UPDATE => 'generateProcessConfigurations',
                WorkflowEvents::WORKFLOW_CREATED => 'workflowCreated',
                WorkflowEvents::WORKFLOW_UPDATED => 'workflowUpdated',
                WorkflowEvents::WORKFLOW_DELETED => 'workflowDeleted'
            ],
            WorkflowDefinitionChangesListener::getSubscribedEvents()
        );
    }

    /**
     * @param WorkflowDefinition $entity
     * @return WorkflowChangesEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEvent(WorkflowDefinition $entity)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $event */
        $event = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->atLeastOnce())->method('getDefinition')->willReturn($entity);

        return $event;
    }

    /**
     * @param array $generatedConfigurations
     */
    protected function setGeneratedConfigurations(array $generatedConfigurations)
    {
        $reflection = new \ReflectionClass('Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionChangesListener');
        $property = $reflection->getProperty('generatedConfigurations');
        $property->setAccessible(true);
        $property->setValue($this->listener, $generatedConfigurations);
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param array $configurations
     */
    protected function assertImportExecuted(WorkflowDefinition $workflowDefinition, array $configurations)
    {
        $this->setGeneratedConfigurations($configurations);
        /** @var ProcessConfigurator|\PHPUnit_Framework_MockObject_MockObject $import */
        $import = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator')
            ->disableOriginalConstructor()
            ->getMock();
        $import->expects($this->any())->method('configureProcesses')
            ->with($configurations[$workflowDefinition->getName()]);

        $this->importLink->expects($this->once())
            ->method('getService')
            ->willReturn($import);
    }

    protected function assertImportNotExecuted()
    {
        $this->generator->expects($this->never())->method($this->anything());
        $this->importLink->expects($this->never())->method($this->anything());
    }

    protected function assertScheduleExecuted()
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
