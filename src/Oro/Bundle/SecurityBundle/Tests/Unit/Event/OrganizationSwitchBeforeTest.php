<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Event;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchBefore;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class OrganizationSwitchBeforeTest extends TestCase
{
    public function testEventInterface(): void
    {
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);
        $organizationToSwitch = $this->createMock(Organization::class);

        $event = new OrganizationSwitchBefore($user, $organization, $organizationToSwitch);

        $this->assertSame($user, $event->getUser());
        $this->assertSame($organization, $event->getOrganization());
        $this->assertSame($organizationToSwitch, $event->getOrganizationToSwitch());

        $organizationToSwitchNew = $this->createMock(Organization::class);
        $event->setOrganizationToSwitch($organizationToSwitchNew);

        $this->assertSame($organizationToSwitchNew, $event->getOrganizationToSwitch());
    }
}
