<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\AssignActiveUser;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\User;

class AssignActiveUserTest extends \PHPUnit\Framework\TestCase
{
    const ATTRIBUTE_NAME = 'some_attribute';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenStorage;

    /** @var AssignActiveUser */
    protected $action;

    protected function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->action = new AssignActiveUser(new ContextAccessor(), $this->tokenStorage);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown()
    {
        unset($this->tokenStorage);
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
                'expectedOptions' => array(
                    'attribute' => new PropertyPath(self::ATTRIBUTE_NAME),
                    'exceptionOnNotFound' => true
                ),
            ),
            'string attribute' => array(
                'inputOptions'    => array('attribute' => new PropertyPath(self::ATTRIBUTE_NAME)),
                'expectedOptions' => array(
                    'attribute' => new PropertyPath(self::ATTRIBUTE_NAME),
                    'exceptionOnNotFound' => true),
            ),
            'exceptionOnNotFound false' => [
                'inputOptions' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_NAME),
                    'exceptionOnNotFound' => false,
                ],
                'expectedOptions' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_NAME),
                    'exceptionOnNotFound' => false,
                ],
            ],
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
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
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
                'exceptionMessage' => 'Only one or two attribute parameters must be defined',
            ),
            'too many options' => array(
                'options' => array(
                    'attribute' => new PropertyPath(self::ATTRIBUTE_NAME),
                    'exceptionOnNotFound' => false,
                    'additional' => 'value'
                ),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Only one or two attribute parameters must be defined',
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

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $context = new ItemStub();

        $this->action->initialize($inputOptions);
        $this->action->execute($context);

        $attributeName = self::ATTRIBUTE_NAME;
        $this->assertEquals($user, $context->$attributeName);
    }
}
