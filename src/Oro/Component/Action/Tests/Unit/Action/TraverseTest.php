<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\Configurable;
use Oro\Component\Action\Action\Traverse;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class TraverseTest extends \PHPUnit\Framework\TestCase
{
    /** @var Configurable|\PHPUnit\Framework\MockObject\MockObject */
    private $configurableAction;

    /** @var Traverse */
    private $action;

    protected function setUp(): void
    {
        $this->configurableAction = $this->createMock(Configurable::class);

        $this->action = new Traverse(new ContextAccessor(), $this->configurableAction);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider initializeDataProvider
     */
    public function testInitialize(array $options)
    {
        $this->assertArrayHasKey(Traverse::OPTION_KEY_ACTIONS, $options);
        $this->configurableAction->expects($this->once())
            ->method('initialize')
            ->with($options[Traverse::OPTION_KEY_ACTIONS]);

        $this->action->initialize($options);
    }

    public function initializeDataProvider(): array
    {
        return [
            'basic' => [
                'options' => [
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                    Traverse::OPTION_KEY_VALUE => new PropertyPath('value'),
                    Traverse::OPTION_KEY_ACTIONS => ['some' => 'actions'],
                ]
            ],
            'plain array with keys' => [
                'options' => [
                    Traverse::OPTION_KEY_ARRAY => ['key' => 'value'],
                    Traverse::OPTION_KEY_KEY => new PropertyPath('key'),
                    Traverse::OPTION_KEY_VALUE => new PropertyPath('value'),
                    Traverse::OPTION_KEY_ACTIONS => ['some' => 'actions'],
                ]
            ],
        ];
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $message)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($message);
        $this->configurableAction->expects($this->never())
            ->method('initialize');
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            'no array' => [
                'options' => [],
                'message' => 'Array parameter is required',
            ],
            'incorrect array' => [
                'options' => [
                    Traverse::OPTION_KEY_ARRAY => 'not_an_array',
                ],
                'message' => 'Array parameter must be either array or valid property definition',
            ],
            'incorrect key' => [
                'options' => [
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                    Traverse::OPTION_KEY_KEY => 'not_a_property_path',
                ],
                'message' => 'Key must be valid property definition',
            ],
            'no value' => [
                'options' => [
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                ],
                'message' => 'Value parameter is required',
            ],
            'incorrect value' => [
                'options' => [
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                    Traverse::OPTION_KEY_VALUE => 'not_a_property_path',
                ],
                'message' => 'Value must be valid property definition',
            ],
            'no actions' => [
                'options' => [
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                    Traverse::OPTION_KEY_VALUE => new PropertyPath('value'),
                ],
                'message' => 'Actions parameter is required',
            ],
            'incorrect actions' => [
                'options' => [
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                    Traverse::OPTION_KEY_VALUE => new PropertyPath('value'),
                    Traverse::OPTION_KEY_ACTIONS => 'not_an_array',
                ],
                'message' => 'Actions must be array',
            ],
        ];
    }

    public function testExecute()
    {
        $context = new StubStorage(
            [
                'array' => ['key_1' => 'value_1', 'key_2' => 'value_2'],
                'key' => null,
                'value' => null,
                'new_array' => [],
            ]
        );

        $options = [
            Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
            Traverse::OPTION_KEY_KEY => new PropertyPath('key'),
            Traverse::OPTION_KEY_VALUE => new PropertyPath('value'),
            Traverse::OPTION_KEY_ACTIONS => ['actions', 'configuration'],
        ];

        $this->configurableAction->expects($this->once())
            ->method('initialize')
            ->with($options[Traverse::OPTION_KEY_ACTIONS]);
        $this->configurableAction->expects($this->any())
            ->method('execute')->with($context)
            ->willReturnCallback(function (StubStorage $context) {
                $key = $context['key'];
                $value = $context['value'];

                $newArray = $context['new_array'];
                $newArray[$key] = $value;
                $context['new_array'] = $newArray;
            });

        $this->action->initialize($options);
        $this->action->execute($context);

        $this->assertNull($context['key']);
        $this->assertNull($context['value']);
        $this->assertEquals($context['array'], $context['new_array']);
    }
}
