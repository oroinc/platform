<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurator;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionChangesListener;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ProcessConfigurationGenerator;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class WorkflowDefinitionChangesListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessConfigurationGenerator */
    protected $generator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessConfigurator */
    protected $processConfigurator;

    /** @var WorkflowDefinitionChangesListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessDefinitionRepository */
    protected $processDefinitionRepository;

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

        $this->processDefinitionRepository = $this
            ->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessDefinitionRepository')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new WorkflowDefinitionChangesListener(
            $this->generator,
            $this->processConfigurator,
            $this->processDefinitionRepository
        );
    }

    protected function tearDown()
    {
        unset($this->listener, $this->generator, $this->processConfigurator);
    }

    public function testGenerateProcessConfigurations()
    {
        $workflowDefinition = (new WorkflowDefinition())->setName('workflow');

        $this->generator->expects($this->once())
            ->method('generateForScheduledTransition')
            ->with($workflowDefinition)->willReturn(['generated']);

        $this->listener->generateProcessConfigurations($this->createEvent($workflowDefinition));

        $this->assertAttributeEquals(
            ['workflow' => ['generated']],
            'generatedConfigurations',
            $this->listener,
            'Generated configuration should be stored for later events.'
        );
    }

    public function testWorkflowAfterCreate()
    {
        $entity = new WorkflowDefinition();
        $entity->setName('test_workflow');

        $this->setValue($this->listener, 'generatedConfigurations', ['test_workflow' => ['generated_config']]);

        $this->processConfigurator->expects($this->any())->method('configureProcesses')
            ->with(['generated_config']);

        $this->listener->workflowAfterCreate($this->createEvent($entity));

        $this->assertAttributeEquals(
            [],
            'generatedConfigurations',
            $this->listener,
            'Processed configuration should be cleared.'
        );
    }

    public function testWorkflowAfterUpdate()
    {
        $entity = new WorkflowDefinition();
        $entity->setName('test_workflow');

        $generatedDefinition = [
            'definitions' => ['newly_generated_process_definition' => ['generated']]
        ];

        $this->setValue(
            $this->listener,
            'generatedConfigurations',
            [
                'test_workflow' => $generatedDefinition
            ]
        );

        $this->processConfigurator->expects($this->any())->method('configureProcesses')
            ->with($generatedDefinition);

        $processIsOk = (new ProcessDefinition())->setName('newly_generated_process_definition');
        $processToDelete = (new ProcessDefinition())->setName('trash_process');

        $this->processDefinitionRepository->expects($this->once())->method('findLikeName')->with(
            'stpn!_!_test!_workflow!_!_%',
            '!'
        )->willReturn([$processIsOk, $processToDelete]);

        $this->processConfigurator->expects($this->once())->method('removeProcesses')->with(['trash_process']);

        $this->listener->workflowAfterUpdate($this->createEvent($entity));
    }

    public function testWorkflowAfterDelete()
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName('test_workflow');
        $processToDelete = (new ProcessDefinition())->setName('trash_process');
        $this->processDefinitionRepository->expects($this->once())->method('findLikeName')->with(
            'stpn!_!_test!_workflow!_!_%',
            '!'
        )->willReturn([$processToDelete]);
        $this->processConfigurator->expects($this->once())->method('removeProcesses')->with(['trash_process']);

        $this->listener->workflowAfterDelete($this->createEvent($workflowDefinition));
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                WorkflowEvents::WORKFLOW_BEFORE_CREATE => 'generateProcessConfigurations',
                WorkflowEvents::WORKFLOW_BEFORE_UPDATE => 'generateProcessConfigurations',
                WorkflowEvents::WORKFLOW_AFTER_CREATE => 'workflowAfterCreate',
                WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'workflowAfterUpdate',
                WorkflowEvents::WORKFLOW_AFTER_DELETE => 'workflowAfterDelete',
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

    protected function assertImportNotExecuted()
    {
        $this->generator->expects($this->never())->method($this->anything());
        $this->processConfigurator->expects($this->never())->method($this->anything());
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

        $this->processConfigurator->expects($this->once())
            ->method('configureProcesses')
            ->with($generatedProcessConfiguration);

        $this->processDefinitionRepository->expects($this->once())
            ->method('findLikeName')
            ->with('stpn!_!_test!_workflow!_!_%', '!')
            ->willReturn([$processDefinition]);

        $this->processConfigurator->expects($this->once())->method('removeProcesses')->with([]);

        $this->listener->workflowActivated($this->createEvent($definition));
    }

    public function testWorkflowDeactivated()
    {
        $definition = new WorkflowDefinition();
        $definition->setName('deactivated_workflow');

        $storedProcess = new ProcessDefinition();
        $storedProcess->setName('process_to_delete');

        $this->processDefinitionRepository->expects($this->once())
            ->method('findLikeName')
            ->with('stpn!_!_deactivated!_workflow!_!_%', '!')
            ->willReturn([$storedProcess]);

        $this->processConfigurator->expects($this->once())
            ->method('removeProcesses')
            ->with(['process_to_delete']);

        $this->listener->workflowDeactivated($this->createEvent($definition));
    }
}
