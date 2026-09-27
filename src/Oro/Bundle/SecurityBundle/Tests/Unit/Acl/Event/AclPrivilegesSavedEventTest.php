<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\Event\AclPrivilegesSavedEvent;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

final class AclPrivilegesSavedEventTest extends TestCase
{
    public function testGetters(): void
    {
        $securityIdentity = $this->createMock(SecurityIdentityInterface::class);
        $privileges = new ArrayCollection([new AclPrivilege()]);

        $event = new AclPrivilegesSavedEvent($securityIdentity, $privileges);

        self::assertSame($securityIdentity, $event->getSecurityIdentity());
        self::assertSame($privileges, $event->getPrivileges());
    }

    public function testName(): void
    {
        self::assertSame('oro_security.acl.privileges_saved', AclPrivilegesSavedEvent::NAME);
    }
}
