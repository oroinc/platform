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

    /**
     * @param \Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        return;
    }

    /**
     * @param \Swift_Events_SendEvent $event
     */
    public function sendPerformed(\Swift_Events_SendEvent $event)
    {
        $message = $event->getMessage();

        $status = $event->getResult();

        $entity = new MassNotification();

        $dateSent = new \DateTime();
        $dateSent->setTimestamp($message->getDate());

        $recipient = key($message->getTo());
        $sender = key($message->getFrom());

        $entity->setEmail($recipient);
        $entity->setSender($sender);
        $entity->setTitle($message->getSubject());
        $entity->setStatus($status);
        $entity->setMessage($message);
        $entity->setScheduledAt($dateSent);
        $entity->setProcessedAt(new \DateTime());
        $entity->setBody($message->getBody());

        $this->em->persist($entity);
        $this->em->flush();
    }

}