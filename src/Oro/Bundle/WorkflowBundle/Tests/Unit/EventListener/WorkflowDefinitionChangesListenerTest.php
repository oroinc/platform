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

use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcesses;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class WorkflowDefinitionChangesListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessConfigurationGenerator */
    protected $generator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink */
    protected $processConfiguratorLink;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink */
    protected $scheduledTransitionProcessesLink;

    /** @var WorkflowDefinitionChangesListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessConfigurator */
    protected $processConfigurator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ScheduledTransitionProcesses */
    protected $scheduledTransitionProcesses;

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

        $this->scheduledTransitionProcesses = $this->getMockBuilder(
            'Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcesses'
        )->disableOriginalConstructor()->getMock();

        $this->processConfiguratorLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->scheduledTransitionProcessesLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WorkflowDefinitionChangesListener(
            $this->generator,
            $this->processConfiguratorLink,
            $this->scheduledTransitionProcessesLink
        );
    }

    protected function tearDown()
    {
        unset($this->listener, $this->generator, $this->processConfiguratorLink);
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

        $this->processConfiguratorLink->expects($this->once())->method('getService')->willReturn(new \stdClass());

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

        $this->scheduledTransitionProcessesLink->expects($this->once())->method('getService')
            ->willReturn(new \stdClass());

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
                WorkflowEvents::WORKFLOW_DELETED => 'workflowDeleted',
                WorkflowEvents::WORKFLOW_ACTIVATED => 'workflowActivated',
                WorkflowEvents::WORKFLOW_DEACTIVATED => 'workflowDeactivated'
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

        $this->processConfiguratorLink->expects($this->once())
            ->method('getService')
            ->willReturn($import);
    }

    protected function assertImportNotExecuted()
    {
        $this->generator->expects($this->never())->method($this->anything());
        $this->processConfiguratorLink->expects($this->never())->method($this->anything());
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

        $this->scheduledTransitionProcessesLink->expects($this->once())
            ->method('getService')
            ->willReturn($scheduleService);
    }

    public function testWorkflowActivated()
    {
        $workflowName = 'test_workflow';
        $definition = new WorkflowDefinition();
        $definition->setName($workflowName);

        $processDefinition = new ProcessDefinition();
        $processDefinition->setName('generated_process');

        $generatedProcessConfiguration = [
            'definitions' => [
                'generated_process' => ['process_config']
            ]
        ];

        $this->generator->expects($this->once())
            ->method('generateForScheduledTransition')
            ->with($definition)
            ->willReturn($generatedProcessConfiguration);

        $this->assertProcessConfiguratorRetrieved();
        $this->processConfigurator->expects($this->once())
            ->method('configureProcesses')
            ->with($generatedProcessConfiguration);

        $this->assertTransitionProcessesRetrieved();

        $this->scheduledTransitionProcesses->expects($this->once())
            ->method('workflowRelated')
            ->with($workflowName)
            ->willReturn([$processDefinition]);

        $this->processConfigurator->expects($this->once())->method('removeProcesses')->with([]);

        $this->listener->workflowActivated($this->createEvent($definition));
    }

    private function assertProcessConfiguratorRetrieved()
    {
        $this->processConfiguratorLink->expects($this->once())
            ->method('getService')
            ->willReturn($this->processConfigurator);
    }

    private function assertTransitionProcessesRetrieved()
    {
        $this->scheduledTransitionProcessesLink->expects($this->once())
            ->method('getService')->willReturn($this->scheduledTransitionProcesses);
    }

    public function testWorkflowDeactivated()
    {
        $definition = new WorkflowDefinition();
        $definition->setName('deactivated_workflow');

        $this->assertTransitionProcessesRetrieved();

        $storedProcess = new ProcessDefinition();
        $storedProcess->setName('process_to_delete');

        $this->scheduledTransitionProcesses->expects($this->once())
            ->method('workflowRelated')
            ->with('deactivated_workflow')
            ->willReturn([$storedProcess]);

        $this->assertProcessConfiguratorRetrieved();
        $this->processConfigurator->expects($this->once())
            ->method('removeProcesses')
            ->with(['process_to_delete']);

        $this->listener->workflowDeactivated($this->createEvent($definition));
    }
}
