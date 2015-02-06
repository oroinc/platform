<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Event;

use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchBefore;

class OrganizationSwitchBeforeTest extends \PHPUnit_Framework_TestCase
{
    public function testEventInterface()
    {
        $user                 = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()->getMock();
        $organization         = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();
        $organizationToSwitch = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();

        $event = new OrganizationSwitchBefore($user, $organization, $organizationToSwitch);

        $this->assertSame($user, $event->getUser());
        $this->assertSame($organization, $event->getOrganization());
        $this->assertSame($organizationToSwitch, $event->getOrganizationToSwitch());

        $organizationToSwitchNew = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()->getMock();
        $event->setOrganizationToSwitch($organizationToSwitchNew);

        $this->assertSame($organizationToSwitchNew, $event->getOrganizationToSwitch());
    }
}
