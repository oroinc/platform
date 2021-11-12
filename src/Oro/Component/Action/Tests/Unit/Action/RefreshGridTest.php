<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\RefreshGrid;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class RefreshGridTest extends \PHPUnit\Framework\TestCase
{
    /** @var RefreshGrid */
    private $action;

    protected function setUp(): void
    {
        $this->action = new RefreshGrid(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitialize()
    {
        $gridName = 'test_grid';

        self::assertInstanceOf(ActionInterface::class, $this->action->initialize([$gridName]));
        self::assertEquals([$gridName], ReflectionUtil::getPropertyValue($this->action, 'gridNames'));
    }

    public function testInitializeException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Gridname parameter must be specified');

        $this->action->initialize([]);
    }

    /**
     * @dataProvider executeMethodProvider
     */
    public function testExecuteMethod(array $inputData, array $expectedData)
    {
        $context = new StubStorage($inputData['context']);

        $this->action->initialize($inputData['options']);
        $this->action->execute($context);

        self::assertEquals($expectedData, $context->getValues());
    }

    public function executeMethodProvider(): array
    {
        return [
            'add grid' => [
                'input' => [
                    'context' => ['param1' => 'value1'],
                    'options' => ['grid1']
                ],
                'expected' => ['param1' => 'value1', 'refreshGrid' => ['grid1']],
            ],
            'merge grids' => [
                'input' => [
                    'context' => ['param2' => 'value2', 'refreshGrid' => ['grid1']],
                    'options' => ['grid2', 'grid2']
                ],
                'expected' => ['param2' => 'value2', 'refreshGrid' => ['grid1', 'grid2']],
            ],
            'with property path' => [
                'input' => [
                    'context' => [
                        'param2' => 'value2',
                        'refreshGrid' => ['grid1'],
                        'testPropertyPath' => 'propertyPathData'
                    ],
                    'options' => ['grid2', new PropertyPath('testPropertyPath')]
                ],
                'expected' => [
                    'param2' => 'value2',
                    'refreshGrid' => ['grid1', 'grid2', 'propertyPathData'],
                    'testPropertyPath' => 'propertyPathData'
                ]
            ]
        ];
    }
}
