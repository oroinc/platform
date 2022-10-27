<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionGroup\PropertyMapper;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class PropertyMapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $mockContextAccessor;

    /** @var PropertyMapper */
    private $propertyMapper;

    protected function setUp(): void
    {
        $this->mockContextAccessor = $this->createMock(ContextAccessor::class);

        $this->propertyMapper = new PropertyMapper($this->mockContextAccessor);
    }

    public function testMapToArgs()
    {
        $pp1 = new PropertyPath('contextParam1');
        $pp2 = new PropertyPath('contextParam2');

        $this->mockContextAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->withConsecutive(
                [[], $pp1],
                [[], $pp2]
            )
            ->willReturnOnConsecutiveCalls(
                'val1',
                'val2'
            );

        $mockExecutionArgs = $this->createMock(ActionGroupExecutionArgs::class);
        $mockExecutionArgs->expects(self::exactly(3))
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

        $this->propertyMapper->toArgs(
            $this->createMock(ActionGroupExecutionArgs::class),
            new \stdClass(),
            []
        );
    }

    public function testTransfer()
    {
        $from = (object)['k' => 'v'];
        $to = (object)['t' => null];

        $sourcePropertyPath = new PropertyPath('source');

        $this->mockContextAccessor->expects(self::once())
            ->method('getValue')
            ->with($from, $sourcePropertyPath)
            ->willReturn('data');

        $this->mockContextAccessor->expects(self::once())
            ->method('setValue')
            ->with($to, 'target', 'data');

        $this->propertyMapper->transfer($from, ['target' => $sourcePropertyPath], $to);
    }
}
