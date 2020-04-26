<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition\AbstractComparison;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PropertyAccess\PropertyPath;

class AbstractComparisonTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractComparison|MockObject */
    protected $condition;

    /** @var MockObject|ContextAccessorInterface */
    protected $contextAccessor;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessorInterface::class);
        $this->condition = new class() extends AbstractComparison {
            public function xgetLeft()
            {
                return $this->left;
            }

            public function xgetRight()
            {
                return $this->right;
            }

            protected function doCompare($left, $right)
            {
            }

            public function getName()
            {
            }
        };
        $this->condition->setContextAccessor($this->contextAccessor);
    }

    /**
     * @dataProvider evaluateDataProvider
     * @param array $options
     * @param array $context
     * @param mixed $expectedValue
     */
    public function testEvaluate(array $options, array $context, $expectedValue)
    {
        /** @var AbstractComparison|MockObject $condition */
        $condition = $this->getMockBuilder(AbstractComparison::class)->getMockForAbstractClass();
        $condition->setContextAccessor($this->contextAccessor);

        $condition->initialize($options);

        $keys     = array_keys($context);
        $leftKey  = reset($keys);
        $rightKey = null;
        if (count($context) > 1) {
            $rightKey = end($keys);
        }

        if ($leftKey && array_key_exists($leftKey, $context)) {
            $left = $context[$leftKey];
        } else {
            $left = $options[0];
        }
        if ($rightKey && array_key_exists($rightKey, $context)) {
            $right = $context[$rightKey];
        } else {
            $right = $options[1];
        }

        $this->contextAccessor->method('hasValue')->willReturn(true);

        $this->contextAccessor->method('getValue')
            ->willReturnCallback(
                function ($context, $value) {
                    return $value instanceof PropertyPath ? $context[(string)$value] : $value;
                }
            );

        $condition->expects(static::once())
            ->method('doCompare')
            ->with($left, $right)
            ->willReturn($expectedValue);

        static::assertEquals($expectedValue, $condition->evaluate($context));
    }

    public function evaluateDataProvider(): array
    {
        return [
            [
                ['left' => new PropertyPath('foo'), 'right' => new PropertyPath('bar')],
                ['foo' => 'fooValue', 'bar' => 'barValue'],
                true
            ],
            [
                [new PropertyPath('foo'), new PropertyPath('bar')],
                ['foo' => 'fooValue', 'bar' => 'barValue'],
                true
            ],
            [
                [null, null],
                [],
                true
            ],
            [
                ['left' => new PropertyPath('foo'), 'right' => new PropertyPath('bar')],
                ['foo' => 'fooValue', 'bar' => 'barValue'],
                false
            ],
            [
                [new PropertyPath('foo'), new PropertyPath('bar')],
                ['foo' => 'fooValue', 'bar' => 'barValue'],
                false
            ],
            [
                [new PropertyPath('foo'), null],
                ['foo' => 'fooValue'],
                false
            ],
        ];
    }

    public function testInitializeSuccess()
    {
        $result = $this->condition->initialize(['left' => 'foo', 'right' => 'bar']);

        static::assertSame($this->condition, $result);
        static::assertEquals('foo', $this->condition->xgetLeft());
        static::assertEquals('bar', $this->condition->xgetRight());
    }

    public function testInitializeFailsWithEmptyRightOption()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "right" is required.');

        $this->condition->initialize(['foo'  => 'bar', 'left' => 'foo']);
    }

    public function testInitializeFailsWithEmptyLeftOption()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "left" is required.');

        $this->condition->initialize(['right' => 'foo', 'foo'   => 'bar',]);
    }

    public function testInitializeFailsWithInvalidOptionsCount()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 2 elements, but 0 given.');

        $this->condition->initialize([]);
    }

    public function testAddError()
    {
        $context = ['foo' => 'fooValue', 'bar' => 'barValue'];
        $options = ['left' => new PropertyPath('foo'), 'right' => new PropertyPath('bar')];

        $left  = $options['left'];
        $right = $options['right'];

        $keys     = array_keys($context);
        $rightKey = end($keys);
        $leftKey  = reset($keys);

        /** @var AbstractComparison|MockObject $condition */
        $condition = $this->getMockBuilder(AbstractComparison::class)->getMockForAbstractClass();
        $condition->setContextAccessor($this->contextAccessor);

        $condition->initialize($options);

        $message = 'Compare {{ left }} with {{ right }}.';
        $condition->setMessage($message);

        $this->contextAccessor->method('hasValue')->willReturn(true);

        $this->contextAccessor->method('getValue')
            ->willReturnMap([
            [$context, $left, $context[$leftKey]],
            [$context, $right, $context[$rightKey]],
        ]);

        $condition->expects(static::once())
            ->method('doCompare')
            ->with($context[$leftKey], $context[$rightKey])
            ->willReturn(false);

        $errors = new ArrayCollection();

        static::assertFalse($condition->evaluate($context, $errors));

        static::assertCount(1, $errors);
        static::assertEquals(
            [
                'message'    => $message,
                'parameters' => ['{{ left }}' => $context[$leftKey], '{{ right }}' => $context[$rightKey]]
            ],
            $errors->get(0)
        );
    }
}
