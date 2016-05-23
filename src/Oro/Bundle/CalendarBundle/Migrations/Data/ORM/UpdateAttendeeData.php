<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;

class UpdateAttendeeData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\CalendarBundle\Migrations\Data\ORM\LoadAttendeeData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->updateOrigin($manager);
        $this->updateStatus($manager);
    }

    /**
     * @param EntityManager $em
     */
    protected function updateOrigin(EntityManager $em)
    {
        $query = $em->createQuery('UPDATE OroCalendarBundle:Attendee a SET a.origin = :origin');
        $query->setParameter('origin', Attendee::ORIGIN_SERVER);
        $query->execute();
    }

    /**
     * @param EntityManager $em
     */
    protected function updateStatus(EntityManager $em)
    {
        $connection = $em->getConnection();
        if (!in_array(
            'invitation_status',
            array_keys($connection->getSchemaManager()->listTableColumns('oro_calendar_event'))
        )) {
            return;
        }

        $connection->executeQuery(<<<SQL
UPDATE
    oro_calendar_event_attendee AS a
SET
    status_id = (
        SELECT
            CASE
                WHEN ce.invitation_status = 'accepted' OR ce.invitation_status = 'declined' THEN ce.invitation_status
                WHEN ce.invitation_status = 'tentatively_accepted' THEN 'tentative'
                ELSE 'none'
            END
        FROM
            oro_calendar_event ce
        WHERE
            ce.related_attendee = a.id
    );
ALTER TABLE oro_calendar_event DROP COLUMN IF EXISTS invitation_status;
SQL
);
    }
}
