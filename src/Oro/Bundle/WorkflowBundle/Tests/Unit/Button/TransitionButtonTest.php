<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Button;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\WorkflowBundle\Button\TransitionButton;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class TransitionButtonTest extends \PHPUnit\Framework\TestCase
{
    /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflow;

    /** @var WorkflowDefinition|\PHPUnit\Framework\MockObject\MockObject */
    protected $definition;

    /** @var Transition|\PHPUnit\Framework\MockObject\MockObject */
    protected $transition;

    /** @var ButtonContext|\PHPUnit\Framework\MockObject\MockObject */
    protected $buttonContext;

    /** @var TransitionButton */
    protected $button;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->transition = $this->getMockBuilder(Transition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->definition = $this->getMockBuilder(WorkflowDefinition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow->expects($this->any())->method('getDefinition')->willReturn($this->definition);

        $this->buttonContext = $this->getMockBuilder(ButtonContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->button = new TransitionButton($this->transition, $this->workflow, $this->buttonContext);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->workflow, $this->definition, $this->button, $this->buttonContext, $this->transition);
    }

    public function testGetName()
    {
        $this->workflow->expects($this->once())->method('getName')->willReturn('test_workflow_name');
        $this->transition->expects($this->once())->method('getName')->willReturn('test_transition_name');

        $this->assertEquals('test_workflow_name_test_transition_name', $this->button->getName());
    }

    public function testGetLabel()
    {
        $label = 'test_label';
        $this->transition->expects($this->once())->method('getButtonLabel')->willReturn($label);

        $this->assertEquals($label, $this->button->getLabel());
    }

    public function testGetIcon()
    {
        $this->assertNull($this->button->getIcon());

        $icon = 'test-icon';
        $this->transition->expects($this->once())->method('getFrontendOptions')->willReturn(['icon' => $icon]);

        $this->assertEquals($icon, $this->button->getIcon());
    }

    public function testGetOrder()
    {
        $this->definition->expects($this->once())->method('getPriority')->willReturn(1);
        $this->assertEquals(1, $this->button->getOrder());
    }

    public function testGetTemplate()
    {
        $this->assertEquals(TransitionButton::DEFAULT_TEMPLATE, $this->button->getTemplate());
    }

    /**
     * @dataProvider getTemplateDataDataProvider
     *
     * @param array $customData
     */
    public function testGetTemplateData(array $customData = [])
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
            'dialogRoute' => null,
            'additionalData' => [],
            'jsDialogWidget' => TransitionButton::TRANSITION_JS_DIALOG_WIDGET,
        ];

        $this->assertEquals(array_merge($defaultData, $customData), $this->button->getTemplateData($customData));
    }

    /**
     * @return array
     */
    public function getTemplateDataDataProvider()
    {
        return [
            'no custom data' => [],
            'with custom data' => ['customData' => ['workflowItem' => 'test_item', 'new index' => 'test value']],
        ];
    }

    public function testGetButtonContext()
    {
        $this->assertInstanceOf(ButtonContext::class, $this->button->getButtonContext());
    }

    public function testGetGroup()
    {
        $this->assertEquals(ButtonInterface::DEFAULT_GROUP, $this->button->getGroup());
    }

    public function testGetWorkflow()
    {
        $this->assertSame($this->workflow, $this->button->getWorkflow());
    }

    public function testGetTransition()
    {
        $this->assertEquals($this->transition, $this->button->getTransition());
    }

    public function testGetTranslationDomain()
    {
        $this->assertEquals('workflows', $this->button->getTranslationDomain());
    }

    public function testClone()
    {
        $newButton = clone $this->button;
        $this->assertEquals($newButton, $this->button);
        $this->assertEquals($newButton->getTransition(), $this->button->getTransition());

        $this->assertNotSame($newButton, $this->button);
        $this->assertNotSame($newButton->getTransition(), $this->button->getTransition());
    }
}
