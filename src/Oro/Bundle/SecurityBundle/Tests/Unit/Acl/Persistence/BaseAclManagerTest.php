<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Persistence;

use Oro\Bundle\SecurityBundle\Acl\Persistence\BaseAclManager;
use Oro\Bundle\SecurityBundle\Model\Role;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseAclManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var BaseAclManager */
    private $manager;

    protected function setUp(): void
    {
        $this->manager = new BaseAclManager();
    }

    public function testGetSid(): void
    {
        self::assertEquals(
            new RoleSecurityIdentity('ROLE_TEST'),
            $this->manager->getSid('ROLE_TEST')
        );

        $src = $this->createMock(Role::class);
        $src->expects(self::once())
            ->method('getRole')
            ->willReturn('ROLE_TEST');
        self::assertEquals(
            new RoleSecurityIdentity('ROLE_TEST'),
            $this->manager->getSid($src)
        );

        $src = $this->createMock(UserInterface::class);
        $src->expects(self::once())
            ->method('getUsername')
            ->willReturn('Test');
        self::assertEquals(
            new UserSecurityIdentity('Test', get_class($src)),
            $this->manager->getSid($src)
        );

        $user = $this->createMock(UserInterface::class);
        $user->expects(self::once())
            ->method('getUsername')
            ->willReturn('Test');
        $src = $this->createMock(TokenInterface::class);
        $src->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        self::assertEquals(
            new UserSecurityIdentity('Test', get_class($user)),
            $this->manager->getSid($src)
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->getSid(new \stdClass());
    }
}
