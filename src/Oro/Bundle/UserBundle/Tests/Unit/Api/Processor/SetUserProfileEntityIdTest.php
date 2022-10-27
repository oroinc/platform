<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\UserBundle\Api\Processor\SetUserProfileEntityId;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SetUserProfileEntityIdTest extends GetProcessorTestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var SetUserProfileEntityId */
    private $processor;

    protected function setUp(): void
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
