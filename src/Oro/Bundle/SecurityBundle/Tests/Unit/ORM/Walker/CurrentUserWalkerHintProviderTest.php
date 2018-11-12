<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker;

use Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalkerHintProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CurrentUserWalkerHintProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenStorage;

    /** @var CurrentUserWalkerHintProvider */
    protected $provider;

    protected function setUp()
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
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
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
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $user = $this->createMock('Oro\Bundle\UserBundle\Entity\AbstractUser');
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
        $token = $this->createMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $user = $this->createMock('Oro\Bundle\UserBundle\Entity\AbstractUser');
        $user->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $organization = $this->createMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $organization->expects($this->once())
            ->method('getId')
            ->willReturn(456);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->once())
            ->method('getOrganizationContext')
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
        $token = $this->createMock('Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface');
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $user = $this->createMock('Oro\Bundle\UserBundle\Entity\AbstractUser');
        $user->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $organization = $this->createMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $organization->expects($this->once())
            ->method('getId')
            ->willReturn(456);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->once())
            ->method('getOrganizationContext')
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
