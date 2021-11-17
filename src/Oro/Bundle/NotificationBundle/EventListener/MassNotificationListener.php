<?php

namespace Oro\Bundle\NotificationBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Adds notifications items with MassNotificationSender::NOTIFICATION_LOG_TYPE log type to the database.
 * @see \Oro\Bundle\NotificationBundle\Model\MassNotificationSender::NOTIFICATION_LOG_TYPE
 */
class MassNotificationListener
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function logMassNotification(NotificationSentEvent $event): void
    {
        if ($event->getType() !== MassNotificationSender::NOTIFICATION_LOG_TYPE) {
            return;
        }

        $message = $event->getMessage();
        if (!$message instanceof SymfonyEmail) {
            // Skips as listener is not designed for non-email messages.
            return;
        }

        $massNotification = $this->createMassNotification($message, $event->getSentCount());
        $entityManager = $this->doctrine->getManagerForClass(MassNotification::class);
        $entityManager->persist($massNotification);
        $entityManager->flush($massNotification);
    }

    private function createMassNotification(SymfonyEmail $symfonyEmail, int $sentCount): MassNotification
    {
        $to = $symfonyEmail->getTo();
        $from = $symfonyEmail->getFrom() ?: [$symfonyEmail->getSender()];
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        return (new MassNotification())
            ->setEmail($this->formatEmail($to))
            ->setSender($this->formatEmail($from))
            ->setSubject($symfonyEmail->getSubject())
            ->setStatus($sentCount > 0 ? MassNotification::STATUS_SUCCESS : MassNotification::STATUS_FAILED)
            ->setScheduledAt($symfonyEmail->getDate() ?: $dateTime)
            ->setProcessedAt($dateTime)
            ->setBody($symfonyEmail->getHtmlBody() ?: $symfonyEmail->getTextBody());
    }

    /**
     * @param SymfonyAddress[] $symfonyAddresses
     *
     * @return string|null
     */
    private function formatEmail(array $symfonyAddresses): ?string
    {
        $symfonyEmail = array_shift($symfonyAddresses);

        return $symfonyEmail ? $symfonyEmail->getName() . ' <' . $symfonyEmail->getAddress() . '>' : null;
    }
}
