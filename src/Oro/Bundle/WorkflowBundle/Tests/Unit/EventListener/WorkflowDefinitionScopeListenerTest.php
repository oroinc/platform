<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionScopeListener;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;

use Oro\Component\Testing\Unit\EntityTrait;

class WorkflowDefinitionScopeListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const FIELD_NAME = 'testField';
    const ENTITY_CLASS = 'stdClass';
    const ENTITY_ID = 42;

    /** @var WorkflowScopeManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowScopeManager;

    /** @var WorkflowDefinitionScopeListener */
    protected $listener;

    protected function setUp()
    {
        $this->workflowScopeManager = $this->getMockBuilder(WorkflowScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WorkflowDefinitionScopeListener($this->workflowScopeManager);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->workflowScopeManager);
    }

    public function testOnCreateWorkflowDefinitionWithEmptyScopesConfig()
    {
        $event = new WorkflowChangesEvent($this->createWorkflowDefinition());

        $this->workflowScopeManager->expects($this->never())->method($this->anything());

        $this->listener->onCreateWorkflowDefinition($event);
    }

    public function testOnCreateWorkflowDefinition()
    {
        $event = new WorkflowChangesEvent($this->createWorkflowDefinition([[self::FIELD_NAME => self::ENTITY_ID]]));

        $this->workflowScopeManager->expects($this->once())->method('updateScopes')->with($event->getDefinition());

        $this->listener->onCreateWorkflowDefinition($event);
    }

    public function testOnUpdateWorkflowDefinitionWithoutScopesConfigChanges()
    {
        $this->workflowScopeManager->expects($this->never())->method($this->anything());

        $event = new WorkflowChangesEvent(
            $this->createWorkflowDefinition(
                [
                    [self::FIELD_NAME => self::ENTITY_CLASS]
                ]
            ),
            $this->createWorkflowDefinition(
                [
                    [self::FIELD_NAME => self::ENTITY_CLASS]
                ]
            )
        );

        $this->listener->onUpdateWorkflowDefinition($event);
    }

    public function testOnUpdateWorkflowDefinition()
    {
        $event = new WorkflowChangesEvent(
            $this->createWorkflowDefinition([[self::FIELD_NAME => self::ENTITY_ID]]),
            $this->createWorkflowDefinition()
        );

        $this->workflowScopeManager->expects($this->once())->method('updateScopes')->with($event->getDefinition());

        $this->listener->onUpdateWorkflowDefinition($event);
    }

    /**
     * @param array $scopesConfig
     * @return WorkflowDefinition
     */
    protected function createWorkflowDefinition(array $scopesConfig = [])
    {
        return $this->getEntity(
            WorkflowDefinition::class,
            [
                'configuration' => ['scopes' => $scopesConfig]
            ]
        );
    }
}
