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

        $this->buttonContext = $this->getMock(ButtonContext::class);

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
        $definition = $this->getMockBuilder(OperationDefinition::class)->disableOriginalConstructor()->getMock();
        $definition->expects($this->once())->method('getOrder')->willReturn(1);

        $this->assertOperationMethodsCalled($this->operation, $definition);

        $this->assertEquals(1, $this->button->getOrder());
    }

    public function testGetTemplate()
    {
        $this->assertEquals(OperationButton::DEFAULT_TEMPLATE, $this->button->getTemplate());
    }

    /**
     * @dataProvider getTemplateDataDataProvider
     *
     * @param null|string $group
     * @param array $expectedResult
     */
    public function testGetTemplateData($group, array $expectedResult)
    {
        $this->buttonContext->expects($this->atLeastOnce())->method('getGroup')->willReturn($group);
        $this->assertOperationMethodsCalled($this->operation, new OperationDefinition());

        $templateData = $this->button->getTemplateData();
        $this->assertEquals($expectedResult, $templateData);
    }

    /**
     * @return array
     */
    public function getTemplateDataDataProvider()
    {
        $customButtonOptions = ['class' => ' btn '];

        return [
            'null as group' => [
                'group' => null,
                'expectedResult' => [
                    'operation' => $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock(),
                    'params' => (new OperationDefinition())->setButtonOptions($customButtonOptions),
                ],
            ],
            OperationRegistry::DEFAULT_GROUP => [
                'group' => OperationRegistry::DEFAULT_GROUP,
                'expectedResult' => [
                    'operation' => $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock(),
                    'params' => (new OperationDefinition())->setButtonOptions($customButtonOptions),
                ],
            ],
            OperationRegistry::VIEW_PAGE_GROUP => [
                'group' => OperationRegistry::VIEW_PAGE_GROUP,
                'expectedResult' => [
                    'operation' => $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock(),
                    'params' => (new OperationDefinition())->setButtonOptions($customButtonOptions),
                ],
            ],
            OperationRegistry::UPDATE_PAGE_GROUP => [
                'group' => OperationRegistry::UPDATE_PAGE_GROUP,
                'expectedResult' => [
                    'operation' => $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock(),
                    'params' => (new OperationDefinition())->setButtonOptions($customButtonOptions),
                ],
            ],
            'custom group' => [
                'group' => uniqid(),
                'expectedResult' => [
                    'operation' => $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock(),
                    'params' => (new OperationDefinition())->setButtonOptions([]),
                ],
            ],
        ];
    }

    public function testGetButtonContext()
    {
        $this->assertInstanceOf(ButtonContext::class, $this->button->getButtonContext());
    }

    /**
     * @param Operation|\PHPUnit_Framework_MockObject_MockObject $operation
     * @param OperationDefinition|\PHPUnit_Framework_MockObject_MockObject $definition
     */
    private function assertOperationMethodsCalled(Operation $operation, OperationDefinition $definition)
    {
        $operation->expects($this->any())->method('getDefinition')->willReturn($definition);
    }

}
