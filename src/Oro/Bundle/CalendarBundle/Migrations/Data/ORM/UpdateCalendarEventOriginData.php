<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class UpdateCalendarEventOriginData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\CalendarBundle\Migrations\Data\ORM\LoadCalendarEventOriginData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $query = $manager->createQuery('UPDATE OroCalendarBundle:CalendarEvent ce SET ce.origin = :origin');
        $query->setParameter('origin', CalendarEvent::ORIGIN_SERVER);
        $query->execute();
    }
}
