<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Event;

use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchAfter;

class OrganizationSwitchAfterTest extends \PHPUnit\Framework\TestCase
{
    public function testEventInterface()
    {
        $user         = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()->getMock();
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();

        $event = new OrganizationSwitchAfter($user, $organization);

        $this->assertSame($user, $event->getUser());
        $this->assertSame($organization, $event->getOrganization());
    }
}
