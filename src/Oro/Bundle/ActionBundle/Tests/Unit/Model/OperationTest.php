<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OperationTest extends \PHPUnit\Framework\TestCase
{
    /** @var OperationDefinition|\PHPUnit\Framework\MockObject\MockObject */
    private $definition;

    /** @var ActionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $actionFactory;

    /** @var ExpressionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $conditionFactory;

    /** @var AttributeAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeAssembler;

    /** @var FormOptionsAssembler|\PHPUnit\Framework\MockObject\MockObject */
    private $formOptionsAssembler;

    /** @var ActionData */
    private $data;

    /** @var Operation */
    private $operation;

    protected function setUp(): void
    {
        $this->definition = $this->createMock(OperationDefinition::class);
        $this->actionFactory = $this->createMock(ActionFactoryInterface::class);
        $this->conditionFactory = $this->createMock(ExpressionFactory::class);
        $this->attributeAssembler = $this->createMock(AttributeAssembler::class);
        $this->formOptionsAssembler = $this->createMock(FormOptionsAssembler::class);
        $this->data = new ActionData();

        $this->operation = new Operation(
            $this->actionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler,
            $this->definition
        );
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
        $this->assertInstanceOf(OperationDefinition::class, $this->operation->getDefinition());
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
     * @dataProvider executeProvider
     */
    public function testExecute(
        ActionData $data,
        array $config,
        array $actions,
        array $conditions,
        string $operationName,
        string $exceptionMessage = ''
    ) {
        $this->definition->expects($this->any())
            ->method('getName')
            ->willReturn($operationName);
        $this->definition->expects($this->any())
            ->method('getConditions')
            ->willReturnMap($config);

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
            $this->expectException(ForbiddenOperationException::class);
            $this->expectExceptionMessage($exceptionMessage);
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

    public function executeProvider(): array
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
     * @dataProvider isAvailableProvider
     */
    public function testIsAvailable(array $inputData, array $expectedData)
    {
        $this->definition->expects($this->any())
            ->method('getConditions')
            ->willReturnMap($inputData['config']['conditions']);

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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function isAvailableProvider(): array
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
     * @dataProvider hasFormProvider
     */
    public function testHasForm(array $input, bool $expected)
    {
        $this->definition->expects($this->once())
            ->method('getFormOptions')
            ->willReturn($input);
        $this->assertEquals($expected, $this->operation->hasForm());
    }

    public function hasFormProvider(): array
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

    public function getFormOptionsDataProvider(): array
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

    private function createAction(
        \PHPUnit\Framework\MockObject\Rule\InvocationOrder $expects,
        ActionData $data
    ): ActionInterface {
        $action = $this->createMock(ActionInterface::class);
        $action->expects($expects)
            ->method('execute')
            ->with($data);

        return $action;
    }

    private function createCondition(
        \PHPUnit\Framework\MockObject\Rule\InvocationOrder $expects,
        ActionData $data,
        bool $returnValue
    ): ConfigurableCondition {
        $condition = $this->createMock(ConfigurableCondition::class);
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

        $this->assertInstanceOf(AttributeManager::class, $attributeManager);
        $this->assertEquals(new ArrayCollection(['test_attr' => $attribute]), $attributeManager->getAttributes());
    }

    public function testClone()
    {
        $attributes = ['attribute' => ['label' => 'attr_label']];

        $definition = new OperationDefinition();
        $definition->setAttributes($attributes)
            ->setConditions(OperationDefinition::PRECONDITIONS, [])
            ->setConditions(OperationDefinition::CONDITIONS, [])
            ->setActions(OperationDefinition::PREACTIONS, [])
            ->setActions(OperationDefinition::ACTIONS, []);

        $this->actionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->createAction($this->any(), $this->data));

        $this->conditionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->createCondition($this->any(), $this->data, true));

        $attribute = new Attribute();
        $attribute->setName('test_attr');

        $this->attributeAssembler->expects($this->any())
            ->method('assemble')
            ->with($this->data, $attributes)
            ->willReturn(new ArrayCollection([$attribute]));

        $operation = new Operation(
            $this->actionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler,
            $definition
        );

        $operation->isAvailable($this->data);
        $operation->init($this->data);
        $operation->execute($this->data);
        $operation->getAttributeManager($this->data);
        $operation->getFormOptions($this->data);

        $newOperation = clone $operation;

        $this->assertEquals($operation, $newOperation);
        $this->assertEquals($operation->getDefinition(), $newOperation->getDefinition());
        $this->assertNotSame($operation->getDefinition(), $newOperation->getDefinition());
    }
}
