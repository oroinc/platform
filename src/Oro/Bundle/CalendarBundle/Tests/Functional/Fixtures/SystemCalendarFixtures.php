<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;

class SystemCalendarFixtures extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $systemCalendar = new SystemCalendar();
        $systemCalendar->setName('Own Calendar');
        $systemCalendar->setOrganization($organization);
        $systemCalendar->setPublic(false);
        $manager->persist($systemCalendar);
        $manager->flush();

        $organization = new Organization();
        $organization->setName('Foreign');
        $organization->setEnabled(true);
        $manager->persist($organization);
        $manager->flush();

        $systemCalendar = new SystemCalendar();
        $systemCalendar->setName('Foreign Calendar');
        $systemCalendar->setOrganization($organization);
        $systemCalendar->setPublic(false);
        $manager->persist($systemCalendar);
        $manager->flush();

        $systemCalendar = new SystemCalendar();
        $systemCalendar->setName('Public Calendar');
        $systemCalendar->setOrganization($organization);
        $systemCalendar->setPublic(true);
        $manager->persist($systemCalendar);
        $manager->flush();
    }
}
