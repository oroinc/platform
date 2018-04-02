<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\NotificationBundle\Entity\Event;

class LoadWorkflowNotificationEvents extends AbstractFixture
{
    const TRANSIT_EVENT = 'oro.workflow.event.notification.workflow_transition';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $eventNames = [
            'transit' => self::TRANSIT_EVENT
        ];

        foreach ($eventNames as $key => $name) {
            $event = new Event($name, sprintf('Event dispatched whenever any workflow transition %ss', $key));
            $manager->persist($event);
        }
        $manager->flush();
    }
}
