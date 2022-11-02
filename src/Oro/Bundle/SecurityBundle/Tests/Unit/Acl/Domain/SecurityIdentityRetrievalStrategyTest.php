<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityRetrievalStrategy;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class SecurityIdentityRetrievalStrategyTest extends \PHPUnit\Framework\TestCase
{
    private SecurityIdentityRetrievalStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new SecurityIdentityRetrievalStrategy();
    }

    public function testGetSecurityIdentities(): void
    {
        $token = $this->createMock(AbstractToken::class);
        $user = new User();
        $user->setUsername('user1');
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $role = 'ROLE1';
        $token->expects(self::once())
            ->method('getRoleNames')
            ->willReturn([$role]);

        $sids = $this->strategy->getSecurityIdentities($token);
        self::assertEquals(
            [
                new UserSecurityIdentity($user->getUsername(), User::class),
                new RoleSecurityIdentity($role)
            ],
            $sids
        );
    }

    public function testGetSecurityIdentitiesForAnonymousToken(): void
    {
        $token = $this->createMock(AnonymousToken::class);
        $role = 'ROLE1';
        $token->expects(self::once())
            ->method('getRoleNames')
            ->willReturn([$role]);

        $sids = $this->strategy->getSecurityIdentities($token);
        self::assertEquals([new RoleSecurityIdentity($role)], $sids);
    }

    public function testGetSecurityIdentitiesWhenUserSidCannotBeCreated(): void
    {
        $token = $this->createMock(AbstractToken::class);
        $user = new User();
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $role = 'ROLE1';
        $token->expects(self::once())
            ->method('getRoleNames')
            ->willReturn([$role]);

        $sids = $this->strategy->getSecurityIdentities($token);
        self::assertEquals([new RoleSecurityIdentity($role)], $sids);
    }
}
