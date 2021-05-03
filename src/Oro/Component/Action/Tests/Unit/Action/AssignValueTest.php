<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\AssignValue;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
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

        $this->action = new class($this->contextAccessor) extends AssignValue {
            public function xgetAssigns(): array
            {
                return $this->assigns;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @dataProvider invalidOptionsNumberDataProvider
     * @param array $options
     */
    public function testInitializeExceptionParametersCount($options)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute and value parameters are required.');

        $this->action->initialize($options);
    }

    public function invalidOptionsNumberDataProvider()
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
     * @param array $options
     */
    public function testInitializeExceptionInvalidAttribute($options)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Attribute must be valid property definition.');

        $this->action->initialize($options);
    }

    public function invalidOptionsAttributeDataProvider()
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
     * @param array $options
     */
    public function testInitialize($options)
    {
        self::assertInstanceOf(AssignValue::class, $this->action->initialize($options));

        if (\is_array(\current($options))) {
            $expectedAssigns = \array_values($options);
        } else {
            $expectedAssigns[] = $options;
        }

        self::assertEquals($expectedAssigns, $this->action->xgetAssigns());
    }

    public function optionsDataProvider()
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
     * @param array $options
     */
    public function testExecute($options)
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
