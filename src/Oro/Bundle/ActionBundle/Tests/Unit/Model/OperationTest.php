<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\Operation;
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

        $this->operation = new Operation(
            $this->actionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler,
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
     * @param ActionData $data
     * @param array $config
     * @param array $actions
     * @param array $conditions
     * @param string $operationName
     * @param string $exceptionMessage
     *
     * @dataProvider executeProvider
     */
    public function testExecute(
        ActionData $data,
        array $config,
        array $actions,
        array $conditions,
        $operationName,
        $exceptionMessage = ''
    ) {
        $this->definition->expects($this->any())->method('getName')->willReturn($operationName);
        $this->definition->expects($this->any())->method('getConditions')->will($this->returnValueMap($config));

        $this->definition->expects($this->any())
            ->method('getActions')
            ->willReturnCallback(function ($name) {
                return [$name];
            });

        $this->actionFactory->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($type, $config) use ($actions) {
                return $actions[$config[0]];
            });

        $this->conditionFactory->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($type, $config) use ($conditions) {
                return $conditions[$config[0]];
            });

        if ($exceptionMessage) {
            $this->setExpectedException(
                'Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException',
                $exceptionMessage
            );
        }

        $errors = new ArrayCollection();

        $this->assertArrayNotHasKey('errors', $data);

        $this->operation->execute($data, $errors);

        $this->assertEmpty($errors->toArray());

        if ($exceptionMessage) {
            $this->assertArrayHasKey('errors', $data);
            $this->assertSame($errors, $data['errors']);
        }
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        $data = new ActionData();

        $config = [
            ['preactions', ['preactions']],
            ['actions', ['actions']],
            ['preconditions', ['preconditions']],
            ['conditions', ['conditions']],
        ];

        return [
            '!isPreConditionAllowed' => [
                'data' => $data,
                'config' => $config,
                'actions' => [
                    'preactions' => $this->createAction($this->once(), $data),
                    'actions' => $this->createAction($this->never(), $data),
                ],
                'conditions' => [
                    'preconditions' => $this->createCondition($this->once(), $data, false),
                    'conditions' => $this->createCondition($this->never(), $data, true),
                ],
                'operationName' => 'TestName1',
                'exception' => 'Operation "TestName1" is not allowed.'
            ],
            '!isConditionAllowed' => [
                'data' => $data,
                'config' => $config,
                'actions' => [
                    'preactions' => $this->createAction($this->once(), $data),
                    'actions' => $this->createAction($this->never(), $data),
                ],
                'conditions' => [
                    'preconditions' => $this->createCondition($this->once(), $data, true),
                    'conditions' => $this->createCondition($this->once(), $data, false),
                ],
                'operationName' => 'TestName2',
                'exception' => 'Operation "TestName2" is not allowed.'
            ],
            'isAllowed' => [
                'data' => $data,
                'config' => $config,
                'actions' => [
                    'preactions' => $this->createAction($this->once(), $data),
                    'actions' => $this->createAction($this->once(), $data),
                ],
                'conditions' => [
                    'preconditions' => $this->createCondition($this->once(), $data, true),
                    'conditions' => $this->createCondition($this->once(), $data, true),
                ],
                'operationName' => 'TestName3',
            ],
        ];
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
            ->method('getConditions')
            ->will($this->returnValueMap($inputData['config']['conditions']));

        $this->definition->expects($this->any())
            ->method('getFormOptions')
            ->willReturn($inputData['config']['form_options']);

        $this->conditionFactory->expects($expectedData['conditionFactory'])
            ->method('create')
            ->willReturnCallback(function ($type, $config) use ($inputData) {
                return $inputData['conditions'][$config[0]];
            });

        $this->assertEquals($expectedData['available'], $this->operation->isAvailable($inputData['data']));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function isAvailableProvider()
    {
        $data = new ActionData();

        return [
            'no conditions' => [
                'input' => [
                    'data' => $data,
                    'config' => [
                        'conditions' => [],
                        'form_options' => [],
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->never(),
                    'available' => true,
                    'errors' => [],
                ],
            ],
            '!isPreConditionAllowed' => [
                'input' => [
                    'data' => $data,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                        'form_options' => [],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $data, false),
                        'conditions' => $this->createCondition($this->never(), $data, true),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(1),
                    'available' => false,
                ],
            ],
            '!isConditionAllowed' => [
                'input' => [
                    'data' => $data,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                        'form_options' => [],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $data, true),
                        'conditions' => $this->createCondition($this->once(), $data, false),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(2),
                    'available' => false,
                    'errors' => ['error3', 'error4'],
                ],
            ],
            'allowed' => [
                'input' => [
                    'data' => $data,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                        'form_options' => [],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $data, true),
                        'conditions' => $this->createCondition($this->once(), $data, true),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(2),
                    'available' => true,
                    'errors' => [],
                ],
            ],
            'hasForm and no conditions' => [
                'input' => [
                    'data' => $data,
                    'config' => [
                        'conditions' => [],
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute1' => [],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->never(),
                    'available' => true,
                    'errors' => [],
                ],
            ],
            'hasForm and !isPreConditionAllowed' => [
                'input' => [
                    'data' => $data,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute2' => [],
                            ],
                        ],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $data, false),
                        'conditions' => $this->createCondition($this->never(), $data, true),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(1),
                    'available' => false,
                ],
            ],
            'hasForm and allowed' => [
                'input' => [
                    'data' => $data,
                    'config' => [
                        'conditions' => [
                            ['preconditions', ['preconditions']],
                            ['conditions', ['conditions']],
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute3' => [],
                            ],
                        ],
                    ],
                    'conditions' => [
                        'preconditions' => $this->createCondition($this->once(), $data, true),
                        'conditions' => $this->createCondition($this->never(), $data, true),
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->exactly(1),
                    'available' => true,
                    'errors' => [],
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
}
