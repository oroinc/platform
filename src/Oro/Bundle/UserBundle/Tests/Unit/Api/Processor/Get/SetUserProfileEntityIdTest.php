<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Api\Processor\Get;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\UserBundle\Api\Processor\Get\SetUserProfileEntityId;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SetUserProfileEntityIdTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenStorage;

    /** @var SetUserProfileEntityId */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->processor = new SetUserProfileEntityId($this->tokenStorage);
    }

    public function testProcessWithoutSecurityToken()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
    }

    public function testProcessWithUnsupportedUserType()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn('someUser');

        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
    }

    public function testProcessWithSupportedUserType()
    {
        $token = $this->createMock(TokenInterface::class);
        $userId = 123;
        $user = $this->getMockBuilder(AbstractUser::class)
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $user->expects(self::once())
            ->method('getId')
            ->willReturn($userId);

        $this->processor->process($this->context);

        self::assertSame($userId, $this->context->getId());
    }
}
