<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionGroup\PropertyMapper;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PropertyAccess\PropertyPath;

class PropertyMapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|ContextAccessor */
    protected $mockContextAccessor;

    /** @var PropertyMapper */
    protected $propertyMapper;

    protected function setUp(): void
    {
        $this->mockContextAccessor = $this->getMockBuilder(ContextAccessor::class)->getMock();
        $this->propertyMapper = new PropertyMapper($this->mockContextAccessor);
    }

    protected function tearDown(): void
    {
        unset($this->mockContextAccessor, $this->propertyMapper);
    }

    public function testMapToArgs()
    {
        $pp1 = new PropertyPath('contextParam1');
        $pp2 = new PropertyPath('contextParam2');

        $this->mockContextAccessor->expects(static::at(0))
            ->method('getValue')
            ->with([], $pp1)
            ->willReturn('val1');
        $this->mockContextAccessor->expects(static::at(1))
            ->method('getValue')
            ->with([], $pp2)
            ->willReturn('val2');

        /** @var MockObject|ActionGroupExecutionArgs $mockExecutionArgs */
        $mockExecutionArgs = $this->getMockBuilder(ActionGroupExecutionArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockExecutionArgs->expects(static::exactly(3))
            ->method('addParameter')
            ->withConsecutive(
                ['arg1', 'val1'],
                ['arg2', ['embedded' => 'val2']],
                ['arg3', 'simple value']
            );

        $this->propertyMapper->toArgs(
            $mockExecutionArgs,
            [
                'arg1' => new PropertyPath('contextParam1'),
                'arg2' => [
                    'embedded' => new PropertyPath('contextParam2')
                ],
                'arg3' => 'simple value',
            ],
            []
        );
    }

    public function testNonTraversableAssertionException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameters map must be array or implements \Traversable interface');

        /** @var MockObject|ActionGroupExecutionArgs $mockExecutionArgs */
        $mockExecutionArgs = $this->getMockBuilder(ActionGroupExecutionArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->propertyMapper->toArgs($mockExecutionArgs, (object)[], []);
    }

    public function testTransfer()
    {
        $from = (object)['k' => 'v'];
        $to = (object)['t' => null];

        $sourcePropertyPath = new PropertyPath('source');

        $this->mockContextAccessor->expects(static::once())
            ->method('getValue')
            ->with($from, $sourcePropertyPath)
            ->willReturn('data');

        $this->mockContextAccessor->expects(static::once())
            ->method('setValue')
            ->with($to, 'target', 'data');

        $this->propertyMapper->transfer($from, ['target' => $sourcePropertyPath], $to);
    }
}
