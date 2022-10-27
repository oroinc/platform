<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;

class AuthenticatedTokenTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testSetUserWithUpdatedListOfRoles()
    {
        $role1 = new Role('ROLE_1');
        $role2 = new Role('ROLE_2');
        $role3 = new Role('ROLE_3');

        $user = new User();
        $user->addUserRole($role1);

        $updatedUser = new User();
        $updatedUser->setSalt($user->getSalt());
        $updatedUser->setUserRoles([$role2, $role3]);

        $token = new UsernamePasswordOrganizationToken($user, 'password', 'main', new Organization(), [$role1]);
        self::assertEquals([$role1], $token->getRoles());
        self::assertTrue($token->isAuthenticated());

        $token->setUser($updatedUser);
        self::assertSame($updatedUser, $token->getUser());
        self::assertEquals([$role2, $role3], $token->getRoles());
        self::assertTrue($token->isAuthenticated());
    }
}
