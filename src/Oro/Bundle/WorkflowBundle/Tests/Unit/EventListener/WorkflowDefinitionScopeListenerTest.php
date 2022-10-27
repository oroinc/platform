<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionScopeListener;
use Oro\Bundle\WorkflowBundle\Scope\WorkflowScopeManager;
use Oro\Component\Testing\Unit\EntityTrait;

class WorkflowDefinitionScopeListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const FIELD_NAME = 'testField';
    private const ENTITY_CLASS = 'stdClass';
    private const ENTITY_ID = 42;

    /** @var WorkflowScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowScopeManager;

    /** @var WorkflowDefinitionScopeListener */
    private $listener;

    protected function setUp(): void
    {
        $this->workflowScopeManager = $this->createMock(WorkflowScopeManager::class);

        $this->listener = new WorkflowDefinitionScopeListener($this->workflowScopeManager);
    }

    public function testOnActivationWorkflowDefinition()
    {
        $event = new WorkflowChangesEvent($this->createWorkflowDefinition());

        $this->workflowScopeManager->expects($this->once())
            ->method('updateScopes')
            ->with($event->getDefinition(), false);

        $this->listener->onActivationWorkflowDefinition($event);
    }

    public function testOnDeactivationWorkflowDefinition()
    {
        $event = new WorkflowChangesEvent($this->createWorkflowDefinition());

        $this->workflowScopeManager->expects($this->once())
            ->method('updateScopes')
            ->with($event->getDefinition(), true);

        $this->listener->onDeactivationWorkflowDefinition($event);
    }

    public function testOnCreateWorkflowDefinitionWithEmptyScopesConfig()
    {
        $event = new WorkflowChangesEvent($this->createWorkflowDefinition());

        $this->workflowScopeManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onCreateWorkflowDefinition($event);
    }

    /**
     * @dataProvider onCreateOrUpdateDataProvider
     *
     * @param WorkflowDefinition $definition
     * @param bool $expectedReset
     */
    public function testOnCreateWorkflowDefinition(WorkflowDefinition $definition, $expectedReset)
    {
        $this->workflowScopeManager->expects($this->once())
            ->method('updateScopes')
            ->with($definition, $expectedReset);

        $this->listener->onCreateWorkflowDefinition(new WorkflowChangesEvent($definition));
    }

    public function testOnUpdateWorkflowDefinitionWithoutScopesConfigChanges()
    {
        $this->workflowScopeManager->expects($this->never())
            ->method($this->anything());

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

    /**
     * @dataProvider onCreateOrUpdateDataProvider
     *
     * @param WorkflowDefinition $definition
     * @param bool $expectedReset
     */
    public function testOnUpdateWorkflowDefinition(WorkflowDefinition $definition, $expectedReset)
    {
        $this->workflowScopeManager->expects($this->once())
            ->method('updateScopes')
            ->with($definition, $expectedReset);

        $this->listener->onUpdateWorkflowDefinition(
            new WorkflowChangesEvent($definition, $this->createWorkflowDefinition())
        );
    }

    public function onCreateOrUpdateDataProvider(): array
    {
        return [
            [
                'definition' => $this->createWorkflowDefinition([[self::FIELD_NAME => self::ENTITY_ID]]),
                'expectedReset' => true
            ],
            [
                'definition' => $this->createWorkflowDefinition([[self::FIELD_NAME => self::ENTITY_ID]], true),
                'expectedReset' => false
            ]
        ];
    }

    /**
     * @param array $scopesConfig
     * @param bool $active
     * @return WorkflowDefinition
     */
    private function createWorkflowDefinition(array $scopesConfig = [], $active = false)
    {
        return $this->getEntity(
            WorkflowDefinition::class,
            [
                'active' => $active,
                'configuration' => [WorkflowDefinition::CONFIG_SCOPES => $scopesConfig],
            ]
        );
    }
}
