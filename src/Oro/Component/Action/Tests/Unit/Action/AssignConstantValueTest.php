<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\AssignConstantValue;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class AssignConstantValueTest extends TestCase
{
    public const TEST_CONSTANT = 'test_c';

    private ActionInterface $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new AssignConstantValue(new ContextAccessor());
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInitializeException(array $options): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->action->initialize($options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            [[]],
            [[1]],
            [[1, 2, 3]],
            [['attribute' => 'attr', 'value' => 'val', 'other' => 4]],
            [['some' => 'test', 'value' => 2]],
            [['some' => 'test', 'attribute' => 'attr']],
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options, string $attribute, string $value): void
    {
        self::assertSame($this->action, $this->action->initialize($options));

        self::assertEquals($attribute, ReflectionUtil::getPropertyValue($this->action, 'attribute'));
        self::assertEquals($value, ReflectionUtil::getPropertyValue($this->action, 'value'));
    }

    public function optionsDataProvider(): array
    {
        return [
            [['attr', 'val'], 'attr', 'val'],
            [['attribute' => 'attr', 'value' => 'val'], 'attr', 'val'],
        ];
    }

    public function testExecuteIncorrectUnknownConstant(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Cannot evaluate value of "someValue", constant is not exist.');

        $value = new PropertyPath('val');
        $attribute = new PropertyPath('attr');

        $context = new \stdClass();
        $context->val = 'someValue';
        $context->attr = null;

        $this->action->initialize([$attribute, $value]);
        $this->action->execute($context);
    }

    public function testExecuteIncorrectNoClass(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Cannot evaluate value of "UnknownClass1000::someValue", class is not exist.');

        $value = new PropertyPath('val');
        $attribute = new PropertyPath('attr');

        $context = new \stdClass();
        $context->val = 'UnknownClass1000::someValue';
        $context->attr = null;

        $this->action->initialize([$attribute, $value]);
        $this->action->execute($context);
    }

    public function testExecuteException(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Action "assign_constant_value" expects a string in parameter "value", array is given.'
        );

        $value = new PropertyPath('val');
        $attribute = new PropertyPath('attr');

        $context = new \stdClass();
        $context->val = ['test', 'other'];
        $context->attr = null;

        $this->action->initialize([$attribute, $value]);
        $this->action->execute($context);
    }

    public function testExecute(): void
    {
        $value = new PropertyPath('val');
        $attribute = new PropertyPath('attr');

        $context = new \stdClass();
        $context->val = self::class . '::TEST_CONSTANT';
        $context->attr = null;

        $this->action->initialize([$attribute, $value]);
        $this->action->execute($context);

        self::assertEquals(self::TEST_CONSTANT, $context->attr);
    }
}
