<?php

namespace Oro\Bundle\NotificationBundle\EventListener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;

class MassNotificationListener
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param NotificationSentEvent $event
     */
    public function logMassNotification(NotificationSentEvent $event)
    {
        $spoolItem = $event->getSpoolItem();
        $sentCount = $event->getSentCount();
        if ($spoolItem->getLogType() === MassNotificationSender::NOTIFICATION_LOG_TYPE) {
            $logEntity = new MassNotification();
            $this->updateFromSwiftMessage($logEntity, $spoolItem->getMessage(), $sentCount);
            $this->em->persist($logEntity);
            $this->em->flush($logEntity);
        }
    }

    /**
     * @param MassNotification $entity
     * @param \Swift_Mime_Message $message
     * @param int $sentCount
     */
    protected function updateFromSwiftMessage(MassNotification $entity, $message, $sentCount)
    {
        $dateScheduled = new \DateTime();
        $dateScheduled->setTimestamp($message->getDate());

        $entity->setEmail($this->formatEmail($message->getTo()));
        $entity->setSender($this->formatEmail($message->getFrom()));
        $entity->setSubject($message->getSubject());
        $entity->setStatus($sentCount > 0 ? MassNotification::STATUS_SUCCESS : MassNotification::STATUS_FAILED);
        $entity->setScheduledAt($dateScheduled);
        $entity->setProcessedAt(new \DateTime());
        $entity->setBody($message->getBody());
    }

    /**
     * @param array $email
     * @return string
     */
    protected function formatEmail($email)
    {
        return current($email) . ' <' . key($email) . '>';
    }
}
