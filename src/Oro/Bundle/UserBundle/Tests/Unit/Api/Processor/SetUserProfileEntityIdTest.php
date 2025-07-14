<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\UserBundle\Api\Processor\SetUserProfileEntityId;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SetUserProfileEntityIdTest extends GetProcessorTestCase
{
    private TokenStorageInterface&MockObject $tokenStorage;
    private SetUserProfileEntityId $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->processor = new SetUserProfileEntityId($this->tokenStorage);
    }

    public function testProcessWithoutSecurityToken(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
    }

    public function testProcessWithUnsupportedUserType(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class));

        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
    }

    public function testProcessWithSupportedUserType(): void
    {
        $userId = 123;
        $user = $this->createMock(AbstractUser::class);
        $user->expects(self::once())
            ->method('getId')
            ->willReturn($userId);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->processor->process($this->context);

        self::assertSame($userId, $this->context->getId());
    }
}
