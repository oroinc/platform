<?php

namespace Oro\Bundle\NotificationBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;

class MassNotificationListener implements \Swift_Events_SendListener
{

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct( EntityManager $em)
    {
        $this->em = $em;
    }

    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        return;
    }

    public function sendPerformed(\Swift_Events_SendEvent $event)
    {
        return;
    }
}