<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\Condition\AbstractComparison;
use Oro\Component\ConfigExpression\ContextAccessorInterface;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\PropertyAccess\PropertyPath;

class AbstractComparisonTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var AbstractComparison */
    private $condition;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessorInterface::class);

        $this->condition = new class() extends AbstractComparison {
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
     */
    public function testEvaluate(array $options, array $context, bool $expectedValue)
    {
        $condition = $this->getMockForAbstractClass(AbstractComparison::class);
        $condition->setContextAccessor($this->contextAccessor);

        $condition->initialize($options);

        $keys = array_keys($context);
        $leftKey = reset($keys);
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

        $this->contextAccessor->expects(self::any())
            ->method('hasValue')
            ->willReturn(true);

        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnCallback(function ($context, $value) {
                return $value instanceof PropertyPath ? $context[(string)$value] : $value;
            });

        $condition->expects(self::once())
            ->method('doCompare')
            ->with($left, $right)
            ->willReturn($expectedValue);

        self::assertEquals($expectedValue, $condition->evaluate($context));
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

        self::assertSame($this->condition, $result);
        self::assertEquals('foo', ReflectionUtil::getPropertyValue($this->condition, 'left'));
        self::assertEquals('bar', ReflectionUtil::getPropertyValue($this->condition, 'right'));
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

        $left = $options['left'];
        $right = $options['right'];

        $leftKey = 'foo';
        $rightKey = 'bar';

        $condition = $this->getMockForAbstractClass(AbstractComparison::class);
        $condition->setContextAccessor($this->contextAccessor);

        $condition->initialize($options);

        $message = 'Compare {{ left }} with {{ right }}.';
        $condition->setMessage($message);

        $this->contextAccessor->expects(self::any())
            ->method('hasValue')
            ->willReturn(true);

        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnMap([
                [$context, $left, $context[$leftKey]],
                [$context, $right, $context[$rightKey]],
            ]);

        $condition->expects(self::once())
            ->method('doCompare')
            ->with($context[$leftKey], $context[$rightKey])
            ->willReturn(false);

        $errors = new ArrayCollection();

        self::assertFalse($condition->evaluate($context, $errors));

        self::assertCount(1, $errors);
        self::assertEquals(
            [
                'message'    => $message,
                'parameters' => ['{{ left }}' => $context[$leftKey], '{{ right }}' => $context[$rightKey]]
            ],
            $errors->get(0)
        );
    }
}
