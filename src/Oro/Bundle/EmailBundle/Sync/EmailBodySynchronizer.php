<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Exception\DisableOriginSyncExceptionInterface;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\EmailBundle\Exception\SyncWithNotificationAlertException;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderSelector;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Synchronizer that syncs the email body data.
 */
class EmailBodySynchronizer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected EmailBodyLoaderSelector $selector;
    protected EventDispatcherInterface $eventDispatcher;
    protected ManagerRegistry $doctrine;
    private NotificationAlertManager $notificationAlertManager;

    /** @var EmailBodyLoaderInterface[] */
    protected array $emailBodyLoaders = [];

    public function __construct(
        EmailBodyLoaderSelector $selector,
        ManagerRegistry $doctrine,
        EventDispatcherInterface $eventDispatcher,
        NotificationAlertManager $notificationAlertManager
    ) {
        $this->selector = $selector;
        $this->doctrine = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
        $this->notificationAlertManager = $notificationAlertManager;
    }

    /**
     * Syncs email body for one email.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function syncOneEmailBody(Email $email, bool $forceSync = false): void
    {
        if ($this->isBodyNotLoaded($email, $forceSync)) {
            // Body loader can load email body from any folder of any emailUser.
            // Even if email body was not loaded, email will be marked as synced to prevent sync degradation in time.
            $em = $this->getManager();
            $bodyLoaded = false;
            $emailBodyChanged = false;
            foreach ($email->getEmailUsers() as $emailUser) {
                $origin = $emailUser->getOrigin();
                if ($origin && $origin->isActive() && $origin->isSyncEnabled()) {
                    foreach ($emailUser->getFolders() as $folder) {
                        [$bodyLoaded, $emailBodyChanged, $newNotifications] = $this->loadBody(
                            $email,
                            $forceSync,
                            $origin,
                            $folder
                        );
                        $this->processNotificationAlerts($origin, $newNotifications, $emailBodyChanged);
                        if ($emailBodyChanged) {
                            $event = new EmailBodyAdded($email);
                            try {
                                $this->eventDispatcher->dispatch($event, EmailBodyAdded::NAME);
                            } catch (\Exception $e) {
                                $bodyLoaded = false;
                                $this->processFailedDuringSaveEmail($email, $e);
                            }
                            break 2;
                        }
                    }
                }
            }
            if (!$bodyLoaded) {
                $this->updateBodySyncedStateForEntity($email);
            } else {
                $email->setBodySynced(true);
                try {
                    $em->persist($email);
                    $em->flush($email);
                    $this->logger->notice(
                        sprintf('The "%s" (ID: %d) email body was synced.', $email->getSubject(), $email->getId())
                    );
                } catch (\Exception $e) {
                    $this->processFailedDuringSaveEmail($email, $e);
                }
            }
        }
    }

    /**
     * Syncs email bodies.
     */
    public function sync(int $maxExecTimeInMin = -1, int $batchSize = 10): void
    {
        $maxExecTimeout = $maxExecTimeInMin > 0
            ? new \DateInterval('PT' . $maxExecTimeInMin . 'M')
            : false;

        $startTime = new \DateTime('now', new \DateTimeZone('UTC'));

        while (true) {
            if ($maxExecTimeout !== false) {
                $date = new \DateTime('now', new \DateTimeZone('UTC'));
                if ($date->sub($maxExecTimeout) >= $startTime) {
                    $this->logger->notice('Exit because allocated time frame elapsed.');
                    break;
                }
            }

            $emailIds = $this->doctrine->getRepository('OroEmailBundle:Email')->getEmailIdsWithoutBody($batchSize);
            if (count($emailIds) === 0) {
                $this->logger->info('All emails was processed');
                break;
            }

            $batchStartTime = new \DateTime('now', new \DateTimeZone('UTC'));

            /** @var Email $email */
            foreach ($emailIds as $emailId) {
                $em = $this->getManager();
                $email = $em->find(Email::class, $emailId);
                if ($email) {
                    $this->syncOneEmailBody($email);
                }
            }
            $this->getManager()->clear();

            $currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $diff        = $currentTime->diff($batchStartTime);
            $this->logger->info(sprintf('Batch save time: %s.', $diff->format('%i minutes %s seconds')));
        }
    }

    protected function getManager(): EntityManager
    {
        $manager = $this->doctrine->getManager();
        if (!$manager->isOpen()) {
            $manager = $this->doctrine->resetManager();
            $manager->clear();

            return $manager;
        }

        return $manager;
    }

    protected function getBodyLoader(EmailOrigin $origin): EmailBodyLoaderInterface
    {
        $originId = $origin->getId();
        if (!isset($this->emailBodyLoaders[$originId])) {
            $this->emailBodyLoaders[$originId] = $this->selector->select($origin);
        }

        return $this->emailBodyLoaders[$originId];
    }

    protected function isBodyNotLoaded(Email $email, bool $forceSync): bool
    {
        return ($email->isBodySynced() !== true || $forceSync === true) && $email->getEmailBody() === null;
    }

    /**
     * @return array [$bodyLoaded, $emailBodyChanged, $notifications]
     */
    protected function loadBody(Email $email, bool $forceSync, EmailOrigin $origin, EmailFolder $folder): array
    {
        $notifications = [];
        $bodyLoaded = false;
        $emailBodyChanged = false;
        $em = $this->getManager();
        $loader = $this->getBodyLoader($origin);
        try {
            $emailBody = $loader->loadEmailBody($folder, $email, $em);
            $bodyLoaded = true;
            // double check
            if ($this->isBodyNotLoaded($email, $forceSync)) {
                $email->setEmailBody($emailBody);
                $emailBodyChanged = true;
            }
        } catch (EmailBodyNotFoundException $e) {
            $this->logger->notice(
                sprintf(
                    'Attempt to load email body from remote server failed. Email id: %d. Error: %s',
                    $email->getId(),
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
            $notifications[] = EmailSyncNotificationAlert::createForGetItemBodyFail(
                $email->getId(),
                'Attempt to load email body failed. Error: ' . $e->getMessage()
            );
        } catch (DisableOriginSyncExceptionInterface $e) {
            $this->disableSyncForOrigin($origin, $em);
            $this->logger->notice(
                sprintf(
                    'Attempt to load email body from remote server failed.Error: %s',
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
            $notifications[] = EmailSyncNotificationAlert::createForRefreshTokenFail();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $this->logger->notice(
                sprintf(
                    'Attempt to load email body failed. Email id: %d. Error: %s',
                    $email->getId(),
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
            $notifications[] = EmailSyncNotificationAlert::createForGetItemBodyFail(
                $email->getId(),
                'Attempt to load email body failed. Error: %s' . $e->getMessage()
            );
        } catch (SyncWithNotificationAlertException  $ex) {
            $this->logger->info(
                sprintf(
                    'Load email body failed. Email id: %d. Error: %s',
                    $email->getId(),
                    $ex->getMessage()
                ),
                ['exception' => $ex->getPrevious()]
            );
            $notifications[] = $ex->getNotificationAlert();
        } catch (\Exception $ex) {
            $this->logger->info(
                sprintf(
                    'Load email body failed. Email id: %d. Error: %s',
                    $email->getId(),
                    $ex->getMessage()
                ),
                ['exception' => $ex]
            );
            $notifications[] = EmailSyncNotificationAlert::createForGetItemBodyFail(
                $email->getId(),
                'Load email body failed. Exception message:' . $ex->getMessage()
            );
        }

        return [$bodyLoaded, $emailBodyChanged, $notifications];
    }

    private function processNotificationAlerts(
        EmailOrigin $origin,
        array $notificationAlerts,
        bool $emailBodyChanged
    ): void {
        $userId = $origin->getOwner()?->getId();
        $organizationId = $origin->getOrganization()->getId();
        $originId = $origin->getId();

        $authAlertsExist = false;
        /** @var $notificationAlert EmailSyncNotificationAlert */
        foreach ($notificationAlerts as $notificationAlert) {
            $authAlertsExist = \in_array(
                $notificationAlert->getAlertType(),
                [
                    EmailSyncNotificationAlert::ALERT_TYPE_AUTH,
                    EmailSyncNotificationAlert::ALERT_TYPE_REFRESH_TOKEN
                ],
                true
            );

            $notificationAlert->setUserId($userId);
            $notificationAlert->setOrganizationId($organizationId);
            $notificationAlert->setEmailOriginId($originId);

            $this->notificationAlertManager->addNotificationAlert($notificationAlert);
        }

        if (false === $authAlertsExist) {
            $this->notificationAlertManager->resolveNotificationAlertsByAlertTypeForUserAndOrganization(
                EmailSyncNotificationAlert::ALERT_TYPE_AUTH,
                $userId,
                $organizationId
            );
            $this->notificationAlertManager->resolveNotificationAlertsByAlertTypeForUserAndOrganization(
                EmailSyncNotificationAlert::ALERT_TYPE_REFRESH_TOKEN,
                $userId,
                $organizationId
            );
        }

        if ($emailBodyChanged) {
            $this->notificationAlertManager->resolveNotificationAlertsByAlertTypeAndStepForUserAndOrganization(
                EmailSyncNotificationAlert::ALERT_TYPE_SYNC,
                EmailSyncNotificationAlert::STEP_GET,
                $userId,
                $organizationId
            );
        }
    }

    private function updateBodySyncedStateForEntity(Email $email): void
    {
        // in case of exception during the entity save, we should save state that email body was synced
        // to prevent sync degradation in time.
        $em = $this->getManager();
        $tableName = $em->getClassMetadata(Email::class)->getTableName();
        $connection = $em->getConnection();
        $connection->update($tableName, ['body_synced' => true], ['id' => $email->getId()]);
    }

    private function processFailedDuringSaveEmail(Email $email, \Exception $exception): void
    {
        $this->updateBodySyncedStateForEntity($email);
        $this->logger->warning(
            sprintf('Load email body failed. Email id: %d. Error: %s', $email->getId(), $exception->getMessage()),
            ['exception' => $exception]
        );
    }

    private function disableSyncForOrigin(EmailOrigin $origin, EntityManager $em): void
    {
        $origin->setIsSyncEnabled(false);
        $em->persist($origin);
        $em->flush();
    }
}
