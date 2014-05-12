<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\NotificationBundle\Entity\Event;

class LoadDefaultNotificationEvents extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $eventNames = array(
            'update' => 'oro.notification.event.entity_post_update',
            'remove' => 'oro.notification.event.entity_post_remove',
            'create' => 'oro.notification.event.entity_post_persist'
        );

        foreach ($eventNames as $key => $name) {
            $event = new Event($name, 'Event dispatched whenever any entity ' . $key . 's');
            $manager->persist($event);
        }
        $manager->flush();
    }
}
