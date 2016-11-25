<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionButton;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class TransitionButtonTest extends \PHPUnit_Framework_TestCase
{
    /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflow;

    /** @var WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject */
    protected $definition;

    /** @var Transition|\PHPUnit_Framework_MockObject_MockObject */
    protected $transition;

    /** @var ButtonContext|\PHPUnit_Framework_MockObject_MockObject */
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
        $defaultData = [
            'workflow' => $this->workflow,
            'transition' => $this->transition,
            'context' => $this->buttonContext,
            'transitionData' => [
                'workflow' => $this->workflow->getName(),
                'transition' => $this->transition->getName(),
                'dialog-route' => $this->buttonContext->getFormDialogRoute(),
                'page-route' => $this->buttonContext->getFormPageRoute(),
                'transition-route' => $this->buttonContext->getExecutionRoute(),
                'transition-condition-messages' => $this->buttonContext->getErrors(),
                'isAllowed' => $this->buttonContext->isEnabled(),
                'enabled' => $this->buttonContext->isEnabled(),
            ],
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
        $this->assertEquals(OperationRegistry::DEFAULT_GROUP, $this->button->getGroup());
    }
}
