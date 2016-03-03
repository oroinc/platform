<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\User\User;

use Oro\Component\Action\Action\AssignActiveUser;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;

class AssignActiveUserTest extends \PHPUnit_Framework_TestCase
{
    const ATTRIBUTE_NAME = 'some_attribute';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * @var AssignActiveUser
     */
    protected $action;

    protected function setUp()
    {
        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new AssignActiveUser(new ContextAccessor(), $this->securityContext);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown()
    {
        unset($this->securityContext);
        unset($this->action);
    }

    /**
     * @param array $inputOptions
     * @param array $expectedOptions
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $inputOptions, array $expectedOptions)
    {
        $this->action->initialize($inputOptions);
        $this->assertAttributeEquals($expectedOptions, 'options', $this->action);
    }

    public function optionsDataProvider()
    {
        return array(
            'numeric attribute' => array(
                'inputOptions'    => array(new PropertyPath(self::ATTRIBUTE_NAME)),
                'expectedOptions' => array('attribute' => new PropertyPath(self::ATTRIBUTE_NAME)),
            ),
            'string attribute' => array(
                'inputOptions'    => array('attribute' => new PropertyPath(self::ATTRIBUTE_NAME)),
                'expectedOptions' => array('attribute' => new PropertyPath(self::ATTRIBUTE_NAME)),
            )
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
            'no options' => array(
                'options' => array(),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Only one attribute parameter must be defined',
            ),
            'too many options' => array(
                'options' => array(
                    'attribute' => new PropertyPath(self::ATTRIBUTE_NAME),
                    'additional' => 'value'
                ),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Only one attribute parameter must be defined',
            ),
            'no attribute' => array(
                'options' => array(
                    'additional' => 'value'
                ),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Attribute must be defined',
            ),
            'not a property path' => array(
                'options' => array(
                    'attribute' => self::ATTRIBUTE_NAME,
                ),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Attribute must be valid property definition',
            ),
        );
    }

    /**
     * @param array $inputOptions
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $inputOptions)
    {
        $user = new User('testUser', 'qwerty');

        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $context = new ItemStub();

        $this->action->initialize($inputOptions);
        $this->action->execute($context);

        $attributeName = self::ATTRIBUTE_NAME;
        $this->assertEquals($user, $context->$attributeName);
    }
}
