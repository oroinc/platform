<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Event\OperationAllowanceEvent;
use Oro\Bundle\ActionBundle\Event\OperationAnnounceEvent;
use Oro\Bundle\ActionBundle\Event\OperationEventDispatcher;
use Oro\Bundle\ActionBundle\Event\OperationExecuteEvent;
use Oro\Bundle\ActionBundle\Event\OperationGuardEvent;
use Oro\Bundle\ActionBundle\Event\OperationPreExecuteEvent;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationServiceInterface;
use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OperationTest extends TestCase
{
    private OperationDefinition&MockObject $definition;
    private ActionFactoryInterface&MockObject $actionFactory;
    private ExpressionFactory&MockObject $conditionFactory;
    private AttributeAssembler&MockObject $attributeAssembler;
    private FormOptionsAssembler&MockObject $formOptionsAssembler;
    private OptionsResolver&MockObject $optionsResolver;
    private OperationEventDispatcher&MockObject $eventDispatcher;
    private ActionData $data;
    private Operation $operation;

    #[\Override]
    protected function setUp(): void
    {
        $this->definition = $this->createMock(OperationDefinition::class);
        $this->actionFactory = $this->createMock(ActionFactoryInterface::class);
        $this->conditionFactory = $this->createMock(ExpressionFactory::class);
        $this->attributeAssembler = $this->createMock(AttributeAssembler::class);
        $this->formOptionsAssembler = $this->createMock(FormOptionsAssembler::class);
        $this->optionsResolver = $this->createMock(OptionsResolver::class);
        $this->eventDispatcher = $this->createMock(OperationEventDispatcher::class);

        $this->data = new ActionData();

        $this->operation = new Operation(
            $this->actionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler,
            $this->optionsResolver,
            $this->eventDispatcher,
            $this->definition
        );
    }

    public function testGetName(): void
    {
        $this->definition->expects($this->once())
            ->method('getName')
            ->willReturn('test name');

        $this->assertEquals('test name', $this->operation->getName());
    }

    public function testIsEnabled(): void
    {
        $this->definition->expects($this->once())
            ->method('getEnabled')
            ->willReturn(true);

        $this->assertEquals(true, $this->operation->isEnabled());
    }

    public function testGetDefinition(): void
    {
        $this->assertInstanceOf(OperationDefinition::class, $this->operation->getDefinition());
    }

    public function testInit(): void
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecute(
        ActionData $data,
        array $config,
        array $actions,
        array $conditions,
        string $operationName,
        string $exceptionMessage = '',
        array $expectedEvents = []
    ): void {
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

        $this->definition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn([]);
        $this->definition->expects($this->once())
            ->method('getButtonOptions')
            ->willReturn([]);

        $this->optionsResolver->expects($this->exactly(3))
            ->method('resolveOptions')
            ->willReturnOnConsecutiveCalls(
                ['enabled' => true],
                [],
                []
            );

        $this->definition->expects($this->once())
            ->method('setFrontendOptions')
            ->with($this->equalTo([]))
            ->willReturnSelf();

        $this->definition->expects($this->once())
            ->method('setButtonOptions')
            ->with($this->equalTo([]))
            ->willReturnSelf();

        $this->definition->expects($this->once())
            ->method('setEnabled')
            ->with($this->equalTo(true))
            ->willReturnSelf();

        $this->definition->expects($this->any())
            ->method('getEnabled')
            ->willReturn(true);

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

        $eventArgs = [];
        foreach ($expectedEvents as $expectedEvent) {
            if ($expectedEvent === 'announce') {
                $eventArgs[] = [new OperationAnnounceEvent($data, $this->definition, $errors)];
            } elseif ($expectedEvent === 'guard') {
                $eventArgs[] = [new OperationGuardEvent($data, $this->definition, $errors)];
            } elseif ($expectedEvent === 'pre_execute') {
                $eventArgs[] = [new OperationPreExecuteEvent($data, $this->definition, $errors)];
            } elseif ($expectedEvent === 'execute') {
                $eventArgs[] = [new OperationExecuteEvent($data, $this->definition, $errors)];
            }
        }
        $this->eventDispatcher->expects($this->exactly(count($expectedEvents)))
            ->method('dispatch')
            ->withConsecutive(...$eventArgs);

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
                'exception' => 'Operation "TestName1" is not allowed.',
                'expectedEvents' => ['announce']
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
                'exception' => 'Operation "TestName2" is not allowed.',
                'expectedEvents' => ['announce', 'guard']
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
                '',
                'expectedEvents' => ['announce', 'guard', 'pre_execute', 'execute']
            ],
        ];
    }

    public function testExecuteService(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();

        $this->definition->expects($this->any())
            ->method('getName')
            ->willReturn('operation_name');
        $this->definition->expects($this->never())
            ->method('getConditions');

        $this->definition->expects($this->never())
            ->method('getActions');
        $this->definition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn([]);
        $this->definition->expects($this->once())
            ->method('getButtonOptions')
            ->willReturn([]);

        $this->optionsResolver->expects($this->exactly(3))
            ->method('resolveOptions')
            ->willReturnOnConsecutiveCalls(
                ['enabled' => true],
                [],
                []
            );

        $this->definition->expects($this->once())
            ->method('setFrontendOptions')
            ->with($this->equalTo([]))
            ->willReturnSelf();

        $this->definition->expects($this->once())
            ->method('setButtonOptions')
            ->with($this->equalTo([]))
            ->willReturnSelf();

        $this->definition->expects($this->once())
            ->method('setEnabled')
            ->with($this->equalTo(true))
            ->willReturnSelf();

        $this->definition->expects($this->any())
            ->method('getEnabled')
            ->willReturn(true);

        $this->actionFactory->expects($this->never())
            ->method('create');

        $this->conditionFactory->expects($this->never())
            ->method('create');

        $this->assertArrayNotHasKey('errors', $data);

        $announceEvent = new OperationAnnounceEvent($data, $this->definition, $errors);
        $guardEvent = new OperationGuardEvent($data, $this->definition, $errors);
        $preExecuteEvent = new OperationPreExecuteEvent($data, $this->definition, $errors);
        $executeEvent = new OperationExecuteEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [$announceEvent],
                [$guardEvent],
                [$preExecuteEvent],
                [$executeEvent],
            );

        $service = $this->createMock(OperationServiceInterface::class);
        $service->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($data, $errors)
            ->willReturn(true);
        $service->expects($this->once())
            ->method('isConditionAllowed')
            ->with($data, $errors)
            ->willReturn(true);
        $service->expects($this->once())
            ->method('execute')
            ->with($data);

        $this->operation->setOperationService($service);

        $this->operation->execute($data, $errors);
    }

    /**
     * @dataProvider isAvailableProvider
     */
    public function testIsAvailable(array $inputData, array $expectedData): void
    {
        $this->definition->expects($this->once())
            ->method('getConditions')
            ->willReturnMap($inputData['config']['conditions']);

        $this->definition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['frontend_option' => true]);

        $this->definition->expects($this->once())
            ->method('getButtonOptions')
            ->willReturn(['button_option' => true]);

        $this->definition->expects($this->once())
            ->method('getEnabled')
            ->willReturn(true);

        $this->optionsResolver->expects($this->exactly(3))
            ->method('resolveOptions')
            ->withConsecutive(
                [$this->equalTo($inputData['data']), ['enabled' => true]],
                [$this->equalTo($inputData['data']), ['frontend_option' => true]],
                [$this->equalTo($inputData['data']), ['button_option' => true]]
            )
            ->willReturnOnConsecutiveCalls(
                ['enabled' => true],
                ['frontend_option' => true],
                ['button_option' => true]
            );

        $this->definition->expects($this->once())
            ->method('setFrontendOptions')
            ->with($this->equalTo(['frontend_option' => true]))
            ->willReturnSelf();

        $this->definition->expects($this->once())
            ->method('setButtonOptions')
            ->with($this->equalTo(['button_option' => true]))
            ->willReturnSelf();

        $this->definition->expects($this->once())
            ->method('setEnabled')
            ->with($this->equalTo(true))
            ->willReturnSelf();

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
                        'preconditions' => $this->createCondition($this->once(), $data, false)
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
                        'preconditions' => $this->createCondition($this->once(), $data, false)
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->once(),
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
                    ],
                ],
                'expected' => [
                    'conditionFactory' => $this->once(),
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

    public function testIsAvailableBlockedByEvent(): void
    {
        $data = new ActionData();

        $event = new OperationAnnounceEvent($data, $this->definition);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(function (OperationAnnounceEvent $event) {
                $event->setAllowed(false);
            });

        $this->assertFalse($this->operation->isAvailable($data));
    }

    public function testIsAllowedBlockedByEventAnnounce(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();

        $this->definition->expects($this->any())
            ->method('getEnabled')
            ->willReturn(true);

        $event = new OperationAnnounceEvent($data, $this->definition, $errors);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event)
            ->willReturnCallback(function (OperationAnnounceEvent $event) {
                $event->setAllowed(false);
            });

        // For simplicity service is used here
        $service = $this->createMock(OperationServiceInterface::class);
        $service->expects($this->any())
            ->method('isPreConditionAllowed')
            ->with($data, $errors)
            ->willReturn(true);
        $service->expects($this->any())
            ->method('isConditionAllowed')
            ->with($data, $errors)
            ->willReturn(true);
        $this->operation->setOperationService($service);

        $this->assertFalse($this->operation->isAllowed($data, $errors));
    }

    public function testIsAllowedBlockedByEventGuard(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();

        $this->definition->expects($this->any())
            ->method('getEnabled')
            ->willReturn(true);
        $this->definition->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn([]);
        $this->definition->expects($this->any())
            ->method('getButtonOptions')
            ->willReturn([]);
        $this->definition->expects($this->any())
            ->method('setFrontendOptions')
            ->willReturnSelf();
        $this->definition->expects($this->any())
            ->method('setButtonOptions')
            ->willReturnSelf();

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (OperationAllowanceEvent $event) {
                if ($event instanceof OperationGuardEvent) {
                    $event->setAllowed(false);
                }
            });

        $this->optionsResolver->expects($this->any())
            ->method('resolveOptions')
            ->willReturn(['enabled' => true]);

        // For simplicity service is used here
        $service = $this->createMock(OperationServiceInterface::class);
        $service->expects($this->any())
            ->method('isPreConditionAllowed')
            ->with($data, $errors)
            ->willReturn(true);
        $service->expects($this->any())
            ->method('isConditionAllowed')
            ->with($data, $errors)
            ->willReturn(true);
        $this->operation->setOperationService($service);

        $this->assertFalse($this->operation->isAllowed($data, $errors));
    }

    /**
     * @dataProvider getFormOptionsDataProvider
     */
    public function testGetFormOptions(array $input, array $expected): void
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
    public function testHasForm(array $input, bool $expected): void
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
        InvocationOrder $expects,
        ActionData $data
    ): ActionInterface {
        $action = $this->createMock(ActionInterface::class);
        $action->expects($expects)
            ->method('execute')
            ->with($data);

        return $action;
    }

    private function createCondition(
        InvocationOrder $expects,
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

    public function testGetAttributeManager(): void
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

    public function testClone(): void
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

        $this->optionsResolver->expects($this->exactly(6))
            ->method('resolveOptions')
            ->willReturnOnConsecutiveCalls(
                ['enabled' => true],
                [],
                [],
                ['enabled' => true],
                [],
                []
            );

        $operation = new Operation(
            $this->actionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler,
            $this->optionsResolver,
            $this->eventDispatcher,
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
