<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action\Stub\StubStorage;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\Action\Traverse;
use Oro\Bundle\ActionBundle\Model\ContextAccessor;

class TraverseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|
     */
    protected $configurableAction;

    /**
     * @var Traverse
     */
    protected $action;

    protected function setUp()
    {
        $this->configurableAction = $this->getMockBuilder('Oro\Component\ConfigExpression\Action\Configurable')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new Traverse(new ContextAccessor(), $this->configurableAction);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @param array $options
     * @dataProvider initializeDataProvider
     */
    public function testInitialize(array $options)
    {
        $this->assertArrayHasKey(Traverse::OPTION_KEY_ACTIONS, $options);
        $this->configurableAction->expects($this->once())->method('initialize')
            ->with($options[Traverse::OPTION_KEY_ACTIONS]);

        $this->action->initialize($options);
    }

    public function initializeDataProvider()
    {
        return array(
            'basic' => array(
                'options' => array(
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                    Traverse::OPTION_KEY_VALUE => new PropertyPath('value'),
                    Traverse::OPTION_KEY_ACTIONS => array('some' => 'actions'),
                )
            ),
            'plain array with keys' => array(
                'options' => array(
                    Traverse::OPTION_KEY_ARRAY => array('key' => 'value'),
                    Traverse::OPTION_KEY_KEY => new PropertyPath('key'),
                    Traverse::OPTION_KEY_VALUE => new PropertyPath('value'),
                    Traverse::OPTION_KEY_ACTIONS => array('some' => 'actions'),
                )
            ),
        );
    }

    /**
     * @param array $options
     * @param $message
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $message)
    {
        $this->setExpectedException('Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException', $message);
        $this->configurableAction->expects($this->never())->method('initialize');
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider()
    {
        return array(
            'no array' => array(
                'options' => array(),
                'message' => 'Array parameter is required',
            ),
            'incorrect array' => array(
                'options' => array(
                    Traverse::OPTION_KEY_ARRAY => 'not_an_array',
                ),
                'message' => 'Array parameter must be either array or valid property definition',
            ),
            'incorrect key' => array(
                'options' => array(
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                    Traverse::OPTION_KEY_KEY => 'not_a_property_path',
                ),
                'message' => 'Key must be valid property definition',
            ),
            'no value' => array(
                'options' => array(
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                ),
                'message' => 'Value parameter is required',
            ),
            'incorrect value' => array(
                'options' => array(
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                    Traverse::OPTION_KEY_VALUE => 'not_a_property_path',
                ),
                'message' => 'Value must be valid property definition',
            ),
            'no actions' => array(
                'options' => array(
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                    Traverse::OPTION_KEY_VALUE => new PropertyPath('value'),
                ),
                'message' => 'Actions parameter is required',
            ),
            'incorrect actions' => array(
                'options' => array(
                    Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
                    Traverse::OPTION_KEY_VALUE => new PropertyPath('value'),
                    Traverse::OPTION_KEY_ACTIONS => 'not_an_array',
                ),
                'message' => 'Actions must be array',
            ),
        );
    }

    public function testExecute()
    {
        $context = new StubStorage(
            array(
                'array' => array('key_1' => 'value_1', 'key_2' => 'value_2'),
                'key' => null,
                'value' => null,
                'new_array' => array(),
            )
        );

        $options = array(
            Traverse::OPTION_KEY_ARRAY => new PropertyPath('array'),
            Traverse::OPTION_KEY_KEY => new PropertyPath('key'),
            Traverse::OPTION_KEY_VALUE => new PropertyPath('value'),
            Traverse::OPTION_KEY_ACTIONS => array('actions', 'configuration'),
        );

        $this->configurableAction->expects($this->once())->method('initialize')
            ->with($options[Traverse::OPTION_KEY_ACTIONS]);
        $this->configurableAction->expects($this->any())->method('execute')->with($context)
            ->will(
                $this->returnCallback(
                    function (StubStorage $context) {
                        $key = $context['key'];
                        $value = $context['value'];

                        $newArray = $context['new_array'];
                        $newArray[$key] = $value;
                        $context['new_array'] = $newArray;
                    }
                )
            );

        $this->action->initialize($options);
        $this->action->execute($context);

        $this->assertNull($context['key']);
        $this->assertNull($context['value']);
        $this->assertEquals($context['array'], $context['new_array']);
    }
}
