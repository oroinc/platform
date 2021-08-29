<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalkerHintProvider;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CurrentUserWalkerHintProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var CurrentUserWalkerHintProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->provider = new CurrentUserWalkerHintProvider($this->tokenStorage);
    }

    public function testGetHintsWithoutToken()
    {
        $this->assertEquals(
            [
                CurrentUserWalker::HINT_SECURITY_CONTEXT => []
            ],
            $this->provider->getHints(true)
        );
    }

    public function testGetHintsWithNotSupportedToken()
    {
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn('test');

        $this->assertEquals(
            [
                CurrentUserWalker::HINT_SECURITY_CONTEXT => []
            ],
            $this->provider->getHints(true)
        );
    }

    public function testGetHintsWithNotOrganizationToken()
    {
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $user = $this->createMock(AbstractUser::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals(
            [
                CurrentUserWalker::HINT_SECURITY_CONTEXT => [
                    'owner' => 123
                ]
            ],
            $this->provider->getHints(true)
        );
    }

    public function testGetHints()
    {
        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $user = $this->createMock(AbstractUser::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $organization = $this->createMock(Organization::class);
        $organization->expects($this->once())
            ->method('getId')
            ->willReturn(456);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->assertEquals(
            [
                CurrentUserWalker::HINT_SECURITY_CONTEXT => [
                    'owner'        => 123,
                    'organization' => 456
                ]
            ],
            $this->provider->getHints(true)
        );
    }

    public function testGetHintsWithCustomFields()
    {
        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $user = $this->createMock(AbstractUser::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $organization = $this->createMock(Organization::class);
        $organization->expects($this->once())
            ->method('getId')
            ->willReturn(456);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->assertEquals(
            [
                CurrentUserWalker::HINT_SECURITY_CONTEXT => [
                    'myUser'         => 123,
                    'myOrganization' => 456
                ]
            ],
            $this->provider->getHints(['user_field' => 'myUser', 'organization_field' => 'myOrganization'])
        );
    }
}
