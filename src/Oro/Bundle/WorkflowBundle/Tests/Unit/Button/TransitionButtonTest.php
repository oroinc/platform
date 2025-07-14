<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Button;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\WorkflowBundle\Button\TransitionButton;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransitionButtonTest extends TestCase
{
    private Workflow&MockObject $workflow;
    private WorkflowDefinition&MockObject $definition;
    private Transition&MockObject $transition;
    private ButtonContext&MockObject $buttonContext;
    private TransitionButton $button;

    #[\Override]
    protected function setUp(): void
    {
        $this->transition = $this->createMock(Transition::class);
        $this->definition = $this->createMock(WorkflowDefinition::class);
        $this->workflow = $this->createMock(Workflow::class);
        $this->buttonContext = $this->createMock(ButtonContext::class);

        $this->workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($this->definition);

        $this->button = new TransitionButton($this->transition, $this->workflow, $this->buttonContext);
    }

    public function testGetName(): void
    {
        $this->workflow->expects($this->once())
            ->method('getName')
            ->willReturn('test_workflow_name');
        $this->transition->expects($this->once())
            ->method('getName')
            ->willReturn('test_transition_name');

        $this->assertEquals('test_workflow_name_test_transition_name', $this->button->getName());
    }

    public function testGetLabel(): void
    {
        $label = 'test_label';
        $this->transition->expects($this->once())
            ->method('getButtonLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->button->getLabel());
    }

    public function testGetIcon(): void
    {
        $this->assertNull($this->button->getIcon());

        $icon = 'test-icon';
        $this->transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['icon' => $icon]);

        $this->assertEquals($icon, $this->button->getIcon());
    }

    public function testGetOrder(): void
    {
        $this->definition->expects($this->once())
            ->method('getPriority')
            ->willReturn(1);
        $this->assertEquals(1, $this->button->getOrder());
    }

    public function testGetTemplate(): void
    {
        $this->assertEquals(TransitionButton::DEFAULT_TEMPLATE, $this->button->getTemplate());
    }

    /**
     * @dataProvider getTemplateDataDataProvider
     */
    public function testGetTemplateData(array $customData = []): void
    {
        $defaultData =             [
            'frontendOptions' => $this->transition->getFrontendOptions(),
            'hasForm' => $this->transition->hasForm(),
            'showDialog' => true,
            'routeParams' => [
                'workflowName' => $this->workflow->getName(),
                'transitionName' => $this->transition->getName(),
                'entityClass' => $this->buttonContext->getEntityClass(),
                'entityId' => $this->buttonContext->getEntityId(),
                'route' => $this->buttonContext->getRouteName(),
                'datagrid' => $this->buttonContext->getDatagridName(),
                'group' => $this->buttonContext->getGroup(),
                'originalUrl' => $this->buttonContext->getOriginalUrl(),
                'workflowItemId' => null
            ],
            'executionRoute' => $this->buttonContext->getExecutionRoute(),
            'requestMethod' => 'POST',
            'dialogRoute' => null,
            'additionalData' => [],
            'jsDialogWidget' => TransitionButton::TRANSITION_JS_DIALOG_WIDGET,
        ];

        $this->assertEquals(array_merge($defaultData, $customData), $this->button->getTemplateData($customData));
    }

    public function getTemplateDataDataProvider(): array
    {
        return [
            'no custom data' => [],
            'with custom data' => ['customData' => ['workflowItem' => 'test_item', 'new index' => 'test value']],
        ];
    }

    public function testGetButtonContext(): void
    {
        $this->assertInstanceOf(ButtonContext::class, $this->button->getButtonContext());
    }

    public function testGetGroup(): void
    {
        $this->assertEquals(ButtonInterface::DEFAULT_GROUP, $this->button->getGroup());
    }

    public function testGetWorkflow(): void
    {
        $this->assertSame($this->workflow, $this->button->getWorkflow());
    }

    public function testGetTransition(): void
    {
        $this->assertEquals($this->transition, $this->button->getTransition());
    }

    public function testGetTranslationDomain(): void
    {
        $this->assertEquals('workflows', $this->button->getTranslationDomain());
    }

    public function testClone(): void
    {
        $newButton = clone $this->button;
        $this->assertEquals($newButton, $this->button);
        $this->assertEquals($newButton->getTransition(), $this->button->getTransition());

        $this->assertNotSame($newButton, $this->button);
        $this->assertNotSame($newButton->getTransition(), $this->button->getTransition());
    }
}
