<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Action\RunActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\PropertyPath;

class RunActionGroupTest extends \PHPUnit\Framework\TestCase
{
    private const ACTION_GROUP_NAME = 'test_action_group';

    /** @var ActionGroupRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $actionGroupRegistry;

    /** @var RunActionGroup */
    private $actionGroup;

    protected function setUp(): void
    {
        $this->actionGroupRegistry = $this->createMock(ActionGroupRegistry::class);

        $this->actionGroup = new RunActionGroup($this->actionGroupRegistry, new ContextAccessor());
        $this->actionGroup->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testOptionNamesRequirements()
    {
        self::assertEquals(RunActionGroup::OPTION_ACTION_GROUP, 'action_group');
        self::assertEquals(RunActionGroup::OPTION_PARAMETERS_MAP, 'parameters_mapping');
        self::assertEquals(RunActionGroup::OPTION_RESULTS, 'results');
        self::assertEquals(RunActionGroup::OPTION_RESULT, 'result');
    }

    public function testInitialize()
    {
        $parametersMap = [
            'entity_class' => 'testClass',
            'entity_id' => 1
        ];

        $options = [
            RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
            RunActionGroup::OPTION_PARAMETERS_MAP => $parametersMap,
            RunActionGroup::OPTION_RESULTS => [],
            RunActionGroup::OPTION_RESULT => new PropertyPath('path')
        ];

        $this->actionGroupRegistry->expects(self::once())
            ->method('getNames')
            ->willReturn([self::ACTION_GROUP_NAME]);

        $result = $this->actionGroup->initialize($options);

        self::assertInstanceOf(ActionInterface::class, $result);
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $inputData, string $exception, string $exceptionMessage)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);

        $this->actionGroupRegistry->expects($this->once())
            ->method('getNames')
            ->willReturn([self::ACTION_GROUP_NAME]);

        $this->actionGroup->initialize($inputData);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            'no action group name' => [
                'inputData' => [],
                'expectedException' => MissingOptionsException::class,
                'expectedExceptionMessage' => sprintf(
                    'The required option "%s" is missing.',
                    RunActionGroup::OPTION_ACTION_GROUP
                )
            ],
            'action group does not exists' => [
                'inputData' => [
                    RunActionGroup::OPTION_ACTION_GROUP => 'non existent'
                ],
                'expectedException' => InvalidOptionsException::class,
                'expectedExceptionMessage' => 'The option "action_group" with value "non existent" is invalid. ' .
                    'Accepted values are: "test_action_group".'
            ],
            'bad parameters map type' => [
                'inputData' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => 'string is not supported'
                ],
                'expectedException' => InvalidOptionsException::class,
                'expectedExceptionMessage' => sprintf(
                    'The option "%s" with value "string is not supported" ' .
                    'is expected to be of type "array", but is of type "string".',
                    RunActionGroup::OPTION_PARAMETERS_MAP
                ),
                $this->createMock(ActionGroup::class)
            ],
            'bad attribute' => [
                'inputData' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
                    RunActionGroup::OPTION_RESULT => '$.nonConvertedPropertyPath'
                ],
                'expectedException' => InvalidOptionsException::class,
                'expectedExceptionMessage' => sprintf(
                    'The option "%s" with value "$.nonConvertedPropertyPath"' .
                    ' is expected to be of type "null" or "Symfony\Component\PropertyAccess\PropertyPathInterface"',
                    RunActionGroup::OPTION_RESULT
                )
            ]
        ];
    }

    public function testExecuteActionWithoutInitialization()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Uninitialized action execution.');

        $this->actionGroup->execute([]);
    }

    /**
     * @dataProvider executeActionDataProvider
     */
    public function testExecuteAction(
        array $context,
        array $options,
        ActionData $arguments,
        ActionData $returnVal,
        ActionData $expected
    ) {
        $data = new ActionData($context);

        $actionGroup = $this->createMock(ActionGroup::class);
        $actionGroup->expects(self::once())
            ->method('execute')
            ->with($arguments)
            ->willReturn($returnVal);


        //during initialize
        $this->actionGroupRegistry->expects(self::once())
            ->method('getNames')
            ->willReturn([self::ACTION_GROUP_NAME]);

        //during execute
        $this->actionGroupRegistry->expects(self::once())
            ->method('get')
            ->with(self::ACTION_GROUP_NAME)
            ->willReturn($actionGroup);

        $this->actionGroup->initialize($options);
        $this->actionGroup->execute($data);

        self::assertEquals($expected, $data);
    }

    public function executeActionDataProvider(): array
    {
        $actionData = $this->createActionData(['paramValue' => 'value']);

        return [
            'without attribute and pass errors' => [
                'contextParams' => [
                    'param' => 'value',
                    'errors' => new ArrayCollection(),
                ],
                'options' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => [
                        'paramValue' => new PropertyPath('param')
                    ]
                ],
                'arguments' => $actionData,
                'return' => new ActionData([]),
                'expected' => $this->createActionData(['param' => 'value', 'errors' => new ArrayCollection()])
            ],
            'with attributes' => [
                'contextParams' => [
                    'param' => 'value',
                ],
                'options' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => [
                        'paramValue' => new PropertyPath('param')
                    ],
                    RunActionGroup::OPTION_RESULTS => ['result' => new PropertyPath('a')],
                ],
                'arguments' => $actionData,
                'return' => new ActionData(['a' => 'A', 'b' => ['B']]),
                'expected' => $this->createActionData(
                    [
                        'param' => 'value',
                        'result' => 'A'
                    ],
                    true
                )
            ],
            'with attribute' => [
                'contextParams' => [
                    'param' => 'value',
                ],
                'options' => [
                    RunActionGroup::OPTION_ACTION_GROUP => self::ACTION_GROUP_NAME,
                    RunActionGroup::OPTION_PARAMETERS_MAP => [
                        'paramValue' => new PropertyPath('param')
                    ],
                    RunActionGroup::OPTION_RESULT => new PropertyPath('all'),
                ],
                'arguments' => $actionData,
                'return' => new ActionData(['a' => 'A', 'b' => ['B']]),
                'expected' => $this->createActionData(
                    [
                        'param' => 'value',
                        'all' => new ActionData(['a' => 'A', 'b' => ['B']])
                    ],
                    true
                )
            ]
        ];
    }

    private function createActionData(array $data, bool $modified = false): ActionData
    {
        if ($modified) {
            $actionData = new ActionData();
            foreach ($data as $name => $value) {
                $actionData->{$name} = $value;
            }
        } else {
            $actionData = new ActionData($data);
        }

        return $actionData;
    }
}
