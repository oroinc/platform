<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Event;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchAfter;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class OrganizationSwitchAfterTest extends TestCase
{
    public function testEventInterface(): void
    {
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);

        $event = new OrganizationSwitchAfter($user, $organization);

        $this->assertSame($user, $event->getUser());
        $this->assertSame($organization, $event->getOrganization());
    }
}
