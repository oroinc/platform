<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\AssignActiveUser;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\InMemoryUser as User;

class AssignActiveUserTest extends \PHPUnit\Framework\TestCase
{
    private const ATTRIBUTE_NAME = 'some_attribute';

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var AssignActiveUser */
    private $action;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->action = new AssignActiveUser(new ContextAccessor(), $this->tokenStorage);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $inputOptions, array $expectedOptions)
    {
        $this->action->initialize($inputOptions);
        self::assertEquals($expectedOptions, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function optionsDataProvider(): array
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
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $exceptionName, string $exceptionMessage)
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
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

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $context = new ItemStub();

        $this->action->initialize($inputOptions);
        $this->action->execute($context);

        $attributeName = self::ATTRIBUTE_NAME;
        self::assertEquals($user, $context->{$attributeName});
    }
}
