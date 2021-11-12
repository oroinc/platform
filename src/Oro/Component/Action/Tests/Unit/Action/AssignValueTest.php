<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\AssignValue;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class AssignValueTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var AssignValue */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);

        $this->action = new AssignValue($this->contextAccessor);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider invalidOptionsNumberDataProvider
     */
    public function testInitializeExceptionParametersCount(array $options)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute and value parameters are required.');

        $this->action->initialize($options);
    }

    public function invalidOptionsNumberDataProvider(): array
    {
        return [
            [[]],
            [[1]],
            [[1, 2, 3]],
            [['target' => 1]],
            [['value' => 1]],
        ];
    }

    /**
     * @dataProvider invalidOptionsAttributeDataProvider
     */
    public function testInitializeExceptionInvalidAttribute(array $options)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property definition.');

        $this->action->initialize($options);
    }

    public function invalidOptionsAttributeDataProvider(): array
    {
        return [
            [['test', 'value']],
            [['attribute' => 'test', 'value' => 'value']]
        ];
    }

    public function testInitializeExceptionNoAttribute()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be defined.');

        $this->action->initialize(['some' => 'test', 'value' => 'test']);
    }

    public function testInitializeExceptionNoValue()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Value must be defined.');

        $this->action->initialize(['attribute' => 'test', 'unknown' => 'test']);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options)
    {
        self::assertInstanceOf(AssignValue::class, $this->action->initialize($options));

        if (\is_array(\current($options))) {
            $expectedAssigns = \array_values($options);
        } else {
            $expectedAssigns[] = $options;
        }

        self::assertEquals($expectedAssigns, ReflectionUtil::getPropertyValue($this->action, 'assigns'));
    }

    public function optionsDataProvider(): array
    {
        $assigns = [
            'numeric arguments' => [
                'options' => [$this->createMock(PropertyPath::class), 'value']
            ],
            'string arguments' => [
                'options' => ['attribute' => $this->createMock(PropertyPath::class), 'value' => 'value']
            ],
            'numeric null value' => [
                'options' => [$this->createMock(PropertyPath::class), null]
            ],
            'string null value' => [
                'options' => ['attribute' => $this->createMock(PropertyPath::class), 'value' => null]
            ],
        ];

        // unite all single assigns to one mass assign
        $assigns['mass assign'] = $assigns;

        return $assigns;
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $options)
    {
        $context = [];
        $optionsData = array_values($options);
        if (is_array(current($optionsData))) {
            $with = [];
            foreach ($optionsData as $item) {
                [$attribute, $value] = array_values($item);
                $with[] = [$context, $attribute, $value];
            }
            $this->contextAccessor->expects(self::exactly(count($optionsData)))
                ->method('setValue')
                ->withConsecutive(...$with);
        } else {
            [$attribute, $value] = $optionsData;
            $this->contextAccessor->expects(self::once())
                ->method('setValue')
                ->with($context, $attribute, $value);
        }

        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
