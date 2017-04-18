<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\NotificationBundle\Entity\Event;

class LoadWorkflowNotificationEvents extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $eventNames = [
            'transit' => 'oro.workflow.event.notification.workflow_transition'
        ];

        foreach ($eventNames as $key => $name) {
            $event = new Event($name, sprintf('Event dispatched whenever any workflow transition %ss', $key));
            $manager->persist($event);
        }
        $manager->flush();
    }
}
