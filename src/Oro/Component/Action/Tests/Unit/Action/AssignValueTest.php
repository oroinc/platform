<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\AssignValue;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class AssignValueTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActionInterface */
    protected $action;

    /** @var MockObject|ContextAccessor */
    protected $contextAccessor;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->getMockBuilder(ContextAccessor::class)->disableOriginalConstructor()->getMock();

        $this->action = new class($this->contextAccessor) extends AssignValue {
            public function xgetAssigns(): array
            {
                return $this->assigns;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
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
        static::assertInstanceOf(ActionInterface::class, $this->action->initialize($options));

        if (\is_array(\current($options))) {
            $expectedAssigns = \array_values($options);
        } else {
            $expectedAssigns[] = $options;
        }

        static::assertEquals($expectedAssigns, $this->action->xgetAssigns());
    }

    public function optionsDataProvider()
    {
        $assigns = [
            'numeric arguments' => [
                'options' => [$this->getPropertyPath(), 'value']
            ],
            'string arguments' => [
                'options' => ['attribute' => $this->getPropertyPath(), 'value' => 'value']
            ],
            'numeric null value' => [
                'options' => [$this->getPropertyPath(), null]
            ],
            'string null value' => [
                'options' => ['attribute' => $this->getPropertyPath(), 'value' => null]
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
            for ($i = 0; $i < count($optionsData); $i++) {
                $assignData = array_values($optionsData[$i]);
                $attribute = $assignData[0];
                $value = $assignData[1];
                $this->contextAccessor->expects(static::at($i))
                    ->method('setValue')
                    ->with($context, $attribute, $value);
            }
        } else {
            $attribute = $optionsData[0];
            $value = $optionsData[1];
            $this->contextAccessor->expects(static::once())
                ->method('setValue')
                ->with($context, $attribute, $value);
        }

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    protected function getPropertyPath()
    {
        return $this->getMockBuilder(PropertyPath::class)->disableOriginalConstructor()->getMock();
    }
}
