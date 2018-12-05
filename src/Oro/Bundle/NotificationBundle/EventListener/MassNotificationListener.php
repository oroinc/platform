<?php

namespace Oro\Bundle\NotificationBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;

/**
 * Adds notifications items with MassNotificationSender::NOTIFICATION_LOG_TYPE log type to the database.
 * @see \Oro\Bundle\NotificationBundle\Model\MassNotificationSender::NOTIFICATION_LOG_TYPE
 */
class MassNotificationListener
{
    /** @var ManagerRegistry */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
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
            $em = $this->doctrine->getManagerForClass(MassNotification::class);
            $em->persist($logEntity);
            $em->flush($logEntity);
        }
    }

    /**
     * @param MassNotification    $entity
     * @param \Swift_Mime_Message $message
     * @param int                 $sentCount
     */
    private function updateFromSwiftMessage(MassNotification $entity, $message, $sentCount)
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
     *
     * @return string
     */
    private function formatEmail($email)
    {
        return current($email) . ' <' . key($email) . '>';
    }
}
