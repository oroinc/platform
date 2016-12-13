<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Button;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\OperationButton;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;

class OperationButtonTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    protected $originOperationName;

    /** @var Operation|\PHPUnit_Framework_MockObject_MockObject */
    protected $operation;

    /** @var OperationDefinition|\PHPUnit_Framework_MockObject_MockObject */
    protected $definition;

    /** @var OperationButton */
    protected $button;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->originOperationName = 'origin_name';
        $this->operation = $this->getOperationMock();

        $this->button = new OperationButton(
            $this->originOperationName,
            $this->operation,
            new ButtonContext(),
            new ActionData()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->operation, $this->button);
    }

    public function testGetName()
    {
        $this->assertEquals($this->originOperationName, $this->button->getName());
    }

    public function testGetLabel()
    {
        $label = 'test_label';
        $definition = $this->getMockBuilder(OperationDefinition::class)->disableOriginalConstructor()->getMock();
        $definition->expects($this->once())->method('getLabel')->willReturn($label);

        $this->assertOperationMethodsCalled($definition);
        $this->assertEquals($label, $this->button->getLabel());
    }

    public function testGetIcon()
    {
        $definition = $this->getMockBuilder(OperationDefinition::class)->disableOriginalConstructor()->getMock();
        $this->assertOperationMethodsCalled($definition);

        $this->assertNull($this->button->getIcon());

        $icon = 'test-icon';
        $definition->expects($this->once())->method('getButtonOptions')->willReturn(['icon' => $icon]);

        $this->assertEquals($icon, $this->button->getIcon());
    }

    public function testGetOrder()
    {
        $order = mt_rand(10, 100);
        $definition = $this->getMockBuilder(OperationDefinition::class)->disableOriginalConstructor()->getMock();
        $definition->expects($this->once())->method('getOrder')->willReturn($order);

        $this->assertOperationMethodsCalled($definition);

        $this->assertEquals($order, $this->button->getOrder());
    }

    public function testGetTemplate()
    {
        $definition = $this->getMockBuilder(OperationDefinition::class)->disableOriginalConstructor()->getMock();
        $this->assertOperationMethodsCalled($definition);
        $this->assertEquals(OperationButton::DEFAULT_TEMPLATE, $this->button->getTemplate());
    }

    /**
     * @dataProvider getTemplateDataDataProvider
     *
     * @param array $expectedResult
     */
    public function testGetTemplateData(array $expectedResult)
    {
        $this->assertOperationMethodsCalled(new OperationDefinition());

        $templateData = $this->button->getTemplateData();
        $this->assertEquals($expectedResult, $templateData);
    }

    /**
     * @return array
     */
    public function getTemplateDataDataProvider()
    {
        return [
            'correct' => [
                'expectedResult' => [
                    'operation' => $this->getOperationMock(),
                    'params' => new OperationDefinition(),
                    'aClass' => '',
                    'actionData' => new ActionData(),
                    'buttonContext' => new ButtonContext(),
                    'additionalData' => []
                ],
            ],
        ];
    }

    public function testGetButtonContext()
    {
        $this->assertInstanceOf(ButtonContext::class, $this->button->getButtonContext());
    }

    public function testGetTemplateWithConfiguredFrontendOptions()
    {
        $templateName = uniqid('test_template', true);
        $definition = $this->getMockBuilder(OperationDefinition::class)->disableOriginalConstructor()->getMock();
        $definition->expects($this->once())->method('getButtonOptions')->willReturn(
            [OperationButton::BUTTON_TEMPLATE_KEY => $templateName]
        );
        $this->assertOperationMethodsCalled($definition);

        $this->assertEquals($templateName, $this->button->getTemplate());
    }

    public function testGetOperation()
    {
        $this->assertEquals($this->operation, $this->button->getOperation());
    }

    /**
     * @param OperationDefinition $definition
     */
    private function assertOperationMethodsCalled(OperationDefinition $definition)
    {
        $this->operation->expects($this->atLeastOnce())->method('getDefinition')->willReturn($definition);
    }

    /**
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getOperationMock()
    {
        return $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
    }
}
