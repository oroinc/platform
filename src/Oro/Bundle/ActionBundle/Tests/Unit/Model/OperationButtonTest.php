<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationButton;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

class OperationButtonTest extends \PHPUnit_Framework_TestCase
{
    /** @var Operation|\PHPUnit_Framework_MockObject_MockObject */
    protected $operation;

    /** @var OperationDefinition|\PHPUnit_Framework_MockObject_MockObject */
    protected $definition;

    /** @var ButtonContext|\PHPUnit_Framework_MockObject_MockObject */
    protected $buttonContext;

    /** @var OperationButton */
    protected $button;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->operation = $this->getMockBuilder(Operation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->buttonContext = $this->getMockBuilder(ButtonContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->button = new OperationButton($this->operation, $this->buttonContext);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->operation, $this->button, $this->buttonContext);
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
