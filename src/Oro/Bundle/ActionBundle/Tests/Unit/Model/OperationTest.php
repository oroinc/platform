<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationActionGroupAssembler;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\Operation\ActionGroupsMappingIterator;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OperationTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationDefinition */
    protected $definition;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionFactory */
    protected $actionFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory */
    protected $conditionFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeAssembler */
    protected $attributeAssembler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormOptionsAssembler */
    protected $formOptionsAssembler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationActionGroupAssembler */
    protected $actionGroupAssembler;

    /** @var Operation */
    protected $operation;

    /** @var ActionData */
    protected $data;

    protected function setUp()
    {
        $this->definition = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formOptionsAssembler = $this->getMockBuilder(
            'Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler'
        )->disableOriginalConstructor()->getMock();

        $this->actionGroupAssembler = $this->getMockBuilder(
            'Oro\Bundle\ActionBundle\Model\Assembler\OperationActionGroupAssembler'
        )->disableOriginalConstructor()->getMock();

        $this->operation = new Operation(
            $this->actionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler,
            $this->actionGroupAssembler,
            $this->definition
        );

        $this->data = new ActionData();
    }

    public function testGetName()
    {
        $this->definition->expects($this->once())
            ->method('getName')
            ->willReturn('test name');

        $this->assertEquals('test name', $this->operation->getName());
    }

    public function testIsEnabled()
    {
        $this->definition->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->assertEquals(true, $this->operation->isEnabled());
    }

    public function testGetDefinition()
    {
        $this->assertInstanceOf('Oro\Bundle\ActionBundle\Model\OperationDefinition', $this->operation->getDefinition());
    }

    public function testInit()
    {
        $config = [
            ['form_init', ['form_init']],
        ];

        $actions = [
            'form_init' => $this->createAction($this->once(), $this->data),
        ];

        $this->definition->expects($this->any())
            ->method('getActions')
            ->willReturnMap($config);

        $this->actionFactory->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($type, $config) use ($actions) {
                return $actions[$config[0]];
            });

        $this->operation->init($this->data);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider isAvailableProvider
     */
    public function testIsAvailable(array $inputData, array $expectedData)
    {
        $this->definition->expects($this->any())
            ->method('getPreconditions')
            ->willReturn($inputData['config']['preconditions']);

        $this->conditionFactory->expects($expectedData['conditionFactory'])
            ->method('create')
            ->willReturn($inputData['preconditions']);

        $this->assertEquals($expectedData['available'], $this->operation->isAvailable($inputData['data']));
    }

    /**
     * @return array
     */
    public function isAvailableProvider()
    {
        $data = new ActionData();

        return [
            'no preconditions' => [
                'input' => [
                    'data' => $data,
                    'config' => [
                        'preconditions' => [],
                        'form_options' => [],
                    ],
                    'preconditions' => [],
                ],
                'expected' => [
                    'conditionFactory' => $this->never(),
                    'available' => true,
                    'errors' => [],
                ],
            ],
            '!isPreconditionAllowed' => [
                'input' => [
                    'data' => $data,
                    'config' => [
                        'preconditions' => ['preconditions'],
                        'form_options' => [],
                    ],
                    'preconditions' => $this->createCondition($this->once(), $data, false),
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(1),
                    'available' => false,
                ],
            ],
            'isPreconditionAllowed' => [
                'input' => [
                    'data' => $data,
                    'config' => [
                        'preconditions' => ['preconditions'],
                        'form_options' => [],
                    ],
                    'preconditions' => $this->createCondition($this->once(), $data, true),
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(1),
                    'available' => true,
                ],
            ],
        ];
    }

    /**
     * @param array $input
     * @param array $expected
     *
     * @dataProvider getFormOptionsDataProvider
     */
    public function testGetFormOptions(array $input, array $expected)
    {
        $this->definition->expects($this->once())
            ->method('getFormOptions')
            ->willReturn($input);

        if ($input) {
            $attributes = ['attribute' => ['label' => 'attr_label']];

            $this->definition->expects($this->once())
                ->method('getAttributes')
                ->willReturn($attributes);

            $attribute = new Attribute();
            $attribute->setName('test_attr');

            $this->attributeAssembler->expects($this->once())
                ->method('assemble')
                ->with($this->data, $attributes)
                ->willReturn(new ArrayCollection(['test_attr' => $attribute]));

            $this->formOptionsAssembler->expects($this->once())
                ->method('assemble')
                ->with($input, new ArrayCollection(['test_attr' => $attribute]))
                ->willReturn($expected);
        }

        $this->assertEquals($expected, $this->operation->getFormOptions($this->data));
    }

    /**
     * @param array $input
     * @param bool $expected
     *
     * @dataProvider hasFormProvider
     */
    public function testHasForm(array $input, $expected)
    {
        $this->definition->expects($this->once())
            ->method('getFormOptions')
            ->willReturn($input);
        $this->assertEquals($expected, $this->operation->hasForm());
    }

    /**
     * @return array
     */
    public function hasFormProvider()
    {
        return [
            'empty' => [
                'input' => [],
                'expected' => false,
            ],
            'empty attribute_fields' => [
                'input' => ['attribute_fields' => []],
                'expected' => false,
            ],
            'filled' => [
                'input' => ['attribute_fields' => ['attribute' => []]],
                'expected' => true,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getFormOptionsDataProvider()
    {
        return [
            'empty' => [
                'input' => [],
                'expected' => [],
            ],
            'filled' => [
                'input' => ['attribute_fields' => ['attribute' => []]],
                'expected' => ['attribute_fields' => ['attribute' => []]],
            ],
        ];
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects
     * @param ActionData $data
     * @return ActionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAction(
        \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects,
        ActionData $data
    ) {
        /* @var $action ActionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $action = $this->getMockBuilder('Oro\Component\Action\Action\ActionInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $action->expects($expects)
            ->method('execute')
            ->with($data);

        return $action;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects
     * @param ActionData $data
     * @param bool $returnValue
     * @return ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createCondition(
        \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expects,
        ActionData $data,
        $returnValue
    ) {
        /* @var $condition ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject */
        $condition = $this->getMockBuilder('Oro\Component\Action\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();

        $condition->expects($expects)
            ->method('evaluate')
            ->with($data)
            ->willReturn($returnValue);

        return $condition;
    }

    public function testGetAttributeManager()
    {
        $attributes = ['attribute' => ['label' => 'attr_label']];

        $this->definition->expects($this->once())
            ->method('getAttributes')
            ->willReturn($attributes);

        $this->data['data'] = new \stdClass();

        $attribute = new Attribute();
        $attribute->setName('test_attr');

        $this->attributeAssembler->expects($this->once())
            ->method('assemble')
            ->with($this->data, $attributes)
            ->willReturn(new ArrayCollection([$attribute]));

        $attributeManager = $this->operation->getAttributeManager($this->data);

        $this->assertInstanceOf('Oro\Bundle\ActionBundle\Model\AttributeManager', $attributeManager);
        $this->assertEquals(new ArrayCollection(['test_attr' => $attribute]), $attributeManager->getAttributes());
    }

    /**
     * @param array $actionGroupsConfig
     * @param ActionData $actionData
     * @dataProvider providerGetActionGroupIterator
     */
    public function testGetActionGroupsIteratorReturnsIt(array $actionGroupsConfig, ActionData $actionData)
    {
        $this->definition->expects($this->once())->method('getActionGroups')->willReturn($actionGroupsConfig);

        if ($actionGroupsConfig) {
            $this->actionGroupAssembler->expects($this->once())->method('assemble')->with($actionGroupsConfig)
                ->willReturn($actionGroupsConfig);
        }

        $iterator = $this->operation->getActionGroupsIterator($actionData);

        $this->assertInstanceOf(
            'Oro\Bundle\ActionBundle\Model\Operation\ActionGroupsMappingIterator',
            $iterator
        );

        $this->assertEquals(
            new Operation\ActionGroupsMappingIterator(
                $actionGroupsConfig,
                $actionData
            ),
            $iterator
        );
    }

    /**
     * @return array
     */
    public function providerGetActionGroupIterator()
    {
        return [
            'no mapping' => [
                'definition config' => [],
                'actionData' => new ActionData()
            ],
            'with values' => [
                'definition config' => ['arg1' => 'value1'],
                'actionData' => new ActionData()
            ],
            'without values - with context' => [
                'definition config' => [],
                'actionData' => new ActionData(['dataKey' => 'datavalue'])
            ],
            'both' => [
                'definition config' => [],
                'actionData' => new ActionData(['dataKey' => 'datavalue'])
            ]
        ];
    }
}
