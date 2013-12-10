<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Model\Action\FormatString;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\ItemStub;

class FormatStringTest extends \PHPUnit_Framework_TestCase
{
    const ATTRIBUTE_PATH = 'attribute';
    const ARGUMENTS_PATH = 'arguments';

    /**
     * @var FormatString
     */
    protected $action;

    /**
     * @var string
     */
    protected $testString = 'some "%param1%" test "%param2%" string';

    /**
     * @var array
     */
    protected $testArguments = array('param1' => 'first', 'param2' => 'second');

    /**
     * @var string
     */
    protected $expectedString = 'some "first" test "second" string';

    protected function setUp()
    {
        $this->action = new FormatString(new ContextAccessor());
    }

    protected function tearDown()
    {
        unset($this->action);
    }

    /**
     * @param array $options
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options)
    {
        $this->action->initialize($options);
        $this->assertAttributeEquals($options, 'options', $this->action);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return array(
            'only string' => array(
                'options' => array(
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => 'some test string'
                ),
                'expected' => 'some test string',
            ),
            'array arguments' => array(
                'options' => array(
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => $this->testString,
                    'arguments' => $this->testArguments,
                ),
                'expected' => $this->expectedString,
            ),
            'property path array arguments' => array(
                'options' => array(
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => $this->testString,
                    'arguments' => new PropertyPath(self::ARGUMENTS_PATH),
                ),
                'expected' => $this->expectedString,
                'arguments' => $this->testArguments,
            ),
            'property path collection arguments' => array(
                'options' => array(
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => $this->testString,
                    'arguments' => new PropertyPath(self::ARGUMENTS_PATH),
                ),
                'expected' => $this->expectedString,
                'arguments' => new ArrayCollection($this->testArguments),
            ),
        );
    }

    /**
     * @param array $options
     * @param string $exceptionName
     * @param string $exceptionMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $exceptionName, $exceptionMessage)
    {
        $this->setExpectedException($exceptionName, $exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return array(
            'no attribute' => array(
                'options' => array(),
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Attribute name parameter is required',
            ),
            'incorrect attribute' => array(
                'options' => array(
                    'attribute' => 'string'
                ),
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Attribute must be valid property definition',
            ),
            'no string' => array(
                'options' => array(
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                ),
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'String parameter must be specified',
            ),
            'incorrect arguments' => array(
                'options' => array(
                    'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
                    'string' => $this->testString,
                    'arguments' => 'not array',
                ),
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Argument parameter must be either array or PropertyPath',
            ),
        );
    }

    /**
     * @param array $options
     * @param string $expected
     * @param mixed $arguments
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $options, $expected, $arguments = null)
    {
        $context = new ItemStub();
        if (null !== $arguments) {
            $argumentsPath = self::ARGUMENTS_PATH;
            $context->$argumentsPath = $arguments;
        }

        $this->action->initialize($options);
        $this->action->execute($context);

        $attributePath = self::ATTRIBUTE_PATH;
        $this->assertEquals($expected, $context->$attributePath);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Argument parameter must be traversable
     */
    public function testNotTraversableArguments()
    {
        $options = array(
            'attribute' => new PropertyPath(self::ATTRIBUTE_PATH),
            'string' => $this->testString,
            'arguments' => new PropertyPath(self::ARGUMENTS_PATH),
        );

        $context = new ItemStub();
        $argumentsPath = self::ARGUMENTS_PATH;
        $context->$argumentsPath = 'not_traversable_value';

        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
