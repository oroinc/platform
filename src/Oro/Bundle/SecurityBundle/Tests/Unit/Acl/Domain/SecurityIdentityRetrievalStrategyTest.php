<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityRetrievalStrategy;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\Role;

class SecurityIdentityRetrievalStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var SecurityIdentityRetrievalStrategy */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new SecurityIdentityRetrievalStrategy();
    }

    public function testGetSecurityIdentities()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = new User();
        $user->setUsername('user1');
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $role = new Role('ROLE1');
        $token->expects(self::once())
            ->method('getRoles')
            ->willReturn([$role]);

        $sids = $this->strategy->getSecurityIdentities($token);
        self::assertEquals(
            [
                new UserSecurityIdentity($user->getUsername(), User::class),
                new RoleSecurityIdentity($role->getRole())
            ],
            $sids
        );
    }

    public function testGetSecurityIdentitiesForAnonymousToken()
    {
        $token = $this->createMock(AnonymousToken::class);
        $role = new Role('ROLE1');
        $token->expects(self::once())
            ->method('getRoles')
            ->willReturn([$role]);

        $sids = $this->strategy->getSecurityIdentities($token);
        self::assertEquals(
            [
                new RoleSecurityIdentity($role->getRole())
            ],
            $sids
        );
    }

    public function testGetSecurityIdentitiesWhenUserSidCannotBeCreated()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = new User();
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $role = new Role('ROLE1');
        $token->expects(self::once())
            ->method('getRoles')
            ->willReturn([$role]);

        $sids = $this->strategy->getSecurityIdentities($token);
        self::assertEquals(
            [
                new RoleSecurityIdentity($role->getRole())
            ],
            $sids
        );
    }
}
