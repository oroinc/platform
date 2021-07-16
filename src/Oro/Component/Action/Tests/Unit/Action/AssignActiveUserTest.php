<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\AssignActiveUser;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\User;

class AssignActiveUserTest extends \PHPUnit\Framework\TestCase
{
    private const ATTRIBUTE_NAME = 'some_attribute';

    /** @var MockObject */
    protected $tokenStorage;

    /** @var AssignActiveUser */
    protected $action;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->action = new class(new ContextAccessor(), $this->tokenStorage) extends AssignActiveUser {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->tokenStorage);
        unset($this->action);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $inputOptions, array $expectedOptions)
    {
        $this->action->initialize($inputOptions);
        static::assertEquals($expectedOptions, $this->action->xgetOptions());
    }

    public function optionsDataProvider()
    {
        return [
            'numeric attribute' => [
                'inputOptions'    => [new PropertyPath(self::ATTRIBUTE_NAME)],
                'expectedOptions' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_NAME),
                    'exceptionOnNotFound' => true
                ],
            ],
            'string attribute' => [
                'inputOptions'    => ['attribute' => new PropertyPath(self::ATTRIBUTE_NAME)],
                'expectedOptions' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_NAME),
                    'exceptionOnNotFound' => true],
            ],
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
        ];
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
        return [
            'no options' => [
                'options' => [],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Only one or two attribute parameters must be defined',
            ],
            'too many options' => [
                'options' => [
                    'attribute' => new PropertyPath(self::ATTRIBUTE_NAME),
                    'exceptionOnNotFound' => false,
                    'additional' => 'value'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Only one or two attribute parameters must be defined',
            ],
            'no attribute' => [
                'options' => [
                    'additional' => 'value'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Attribute must be defined',
            ],
            'not a property path' => [
                'options' => [
                    'attribute' => self::ATTRIBUTE_NAME,
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Attribute must be valid property definition',
            ],
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $inputOptions)
    {
        $user = new User('testUser', 'qwerty');

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        $token->expects(static::once())->method('getUser')->willReturn($user);
        $this->tokenStorage->expects(static::once())->method('getToken')->willReturn($token);

        $context = new ItemStub();

        $this->action->initialize($inputOptions);
        $this->action->execute($context);

        $attributeName = self::ATTRIBUTE_NAME;
        static::assertEquals($user, $context->$attributeName);
    }
}
