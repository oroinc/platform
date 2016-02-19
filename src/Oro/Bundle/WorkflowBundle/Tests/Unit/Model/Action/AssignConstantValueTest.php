<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\Action\ActionInterface;
use Oro\Component\ConfigExpression\Action\AssignConstantValue;
use Oro\Bundle\ActionBundle\Model\ContextAccessor;

class AssignConstantValueTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CONSTANT = 'test_c';

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var ActionInterface
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();

        $this->action = new AssignConstantValue($this->contextAccessor);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @dataProvider invalidOptionsDataProvider
     * @param array $options
     */
    public function testInitializeException(array $options)
    {
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
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
     * @param array $options
     * @param string $attribute
     * @param string $value
     */
    public function testInitialize(array $options, $attribute, $value)
    {
        $this->assertSame($this->action, $this->action->initialize($options));

        $this->assertAttributeEquals($attribute, 'attribute', $this->action);
        $this->assertAttributeEquals($value, 'value', $this->action);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            [['attr', 'val'], 'attr', 'val'],
            [['attribute' => 'attr', 'value' => 'val'], 'attr', 'val'],
        ];
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Cannot evaluate value of "someValue", constant is not exist.
     */
    public function testExecuteIncorrectUnknownConstant()
    {
        $value = new PropertyPath('val');
        $attribute = new PropertyPath('attr');

        $context = new \stdClass();
        $context->val = 'someValue';
        $context->attr = null;

        $this->action->initialize([$attribute, $value]);
        $this->action->execute($context);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Cannot evaluate value of "UnknownClass1000::someValue", class is not exist.
     */
    public function testExecuteIncorrectNoClass()
    {
        $value = new PropertyPath('val');
        $attribute = new PropertyPath('attr');

        $context = new \stdClass();
        $context->val = 'UnknownClass1000::someValue';
        $context->attr = null;

        $this->action->initialize([$attribute, $value]);
        $this->action->execute($context);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Action "assign_constant_value" expects a string in parameter "value", array is given.
     */
    public function testExecuteException()
    {
        $value = new PropertyPath('val');
        $attribute = new PropertyPath('attr');

        $context = new \stdClass();
        $context->val = ['test', 'other'];
        $context->attr = null;

        $this->action->initialize([$attribute, $value]);
        $this->action->execute($context);
    }

    public function testExecute()
    {
        $value = new PropertyPath('val');
        $attribute = new PropertyPath('attr');

        $context = new \stdClass();
        $context->val = 'Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action\AssignConstantValueTest::TEST_CONSTANT';
        $context->attr = null;

        $this->action->initialize([$attribute, $value]);
        $this->action->execute($context);

        $this->assertEquals(self::TEST_CONSTANT, $context->attr);
    }
}
