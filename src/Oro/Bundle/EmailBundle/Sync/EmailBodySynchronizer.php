<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyFailedException;
use Oro\Bundle\EmailBundle\Exception\SyncWithNotificationAlertException;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderSelector;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;
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
    protected ?EntityManager $manager = null;

    /**
     * EmailBodySynchronizer constructor.
     */
    public function __construct(
        EmailBodyLoaderSelector $selector,
        ManagerRegistry $doctrine,
        EventDispatcherInterface $eventDispatcher,
        NotificationAlertManager $notificationAlertManager
    ) {
        $this->selector        = $selector;
        $this->doctrine        = $doctrine;
        $this->eventDispatcher = $eventDispatcher;
        $this->notificationAlertManager = $notificationAlertManager;
    }

    /**
     * Syncs email body for one email
     *
     * @param Email $email
     * @param bool $forceSync
     *
     * @throws LoadEmailBodyFailedException
     */
    public function syncOneEmailBody(Email $email, $forceSync = false)
    {
        if ($this->isBodyNotLoaded($email, $forceSync)) {
            // Body loader can load email body from any folder of any emailUser.
            // Even if email body was not loaded, email will be marked as synced to prevent sync degradation in time.
            $em = $this->getManager();
            $bodyLoaded = false;
            foreach ($email->getEmailUsers() as $emailUser) {
                if (($origin = $emailUser->getOrigin()) && $origin->isActive()) {
                    foreach ($emailUser->getFolders() as $folder) {
                        [$bodyLoaded, $emailBodyChanged, $notifications] = $this->loadBody(
                            $email,
                            $forceSync,
                            $origin,
                            $folder
                        );
                        $this->processNotificationAlerts($origin, $notifications, $emailBodyChanged);
                        if ($emailBodyChanged) {
                            $event = new EmailBodyAdded($email);
                            $this->eventDispatcher->dispatch($event, EmailBodyAdded::NAME);
                            break 2;
                        }
                    }
                }
            }
            $email->setBodySynced(true);
            $em->persist($email);
            $em->flush($email);
            if (!$bodyLoaded) {
                throw new LoadEmailBodyFailedException($email);
            }
        }
    }

    /**
     * Syncs email bodies
     *
     * @param int $maxExecTimeInMin
     * @param int $batchSize
     */
    public function sync($maxExecTimeInMin = -1, $batchSize = 10)
    {
        $repo           = $this->doctrine->getRepository('OroEmailBundle:Email');
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

            $emails = $repo->getEmailsWithoutBody($batchSize);
            if (count($emails) === 0) {
                $this->logger->info('All emails was processed');
                break;
            }

            $batchStartTime = new \DateTime('now', new \DateTimeZone('UTC'));

            /** @var Email $email */
            foreach ($emails as $email) {
                try {
                    $this->syncOneEmailBody($email);
                    $this->logger->notice(
                        sprintf('The "%s" (ID: %d) email body was synced.', $email->getSubject(), $email->getId())
                    );
                } catch (\Exception $e) {
                    // in case of exception, we should save state that email body was synced.
                    $this->getManager()->persist($email);
                    continue;
                }
            }
            $this->getManager()->clear();

            $currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $diff        = $currentTime->diff($batchStartTime);
            $this->logger->info(sprintf('Batch save time: %s.', $diff->format('%i minutes %s seconds')));
        }
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->doctrine->getManager();
        }

        return $this->manager;
    }

    /**
     * @param EmailOrigin $origin
     *
     * @return EmailBodyLoaderInterface
     */
    protected function getBodyLoader(EmailOrigin $origin)
    {
        $originId = $origin->getId();
        if (!isset($this->emailBodyLoaders[$originId])) {
            $this->emailBodyLoaders[$originId] = $this->selector->select($origin);
        }

        return $this->emailBodyLoaders[$originId];
    }

    /**
     * @param Email $email
     * @param bool $forceSync
     *
     * @return bool
     */
    protected function isBodyNotLoaded(Email $email, $forceSync)
    {
        return ($email->isBodySynced() !== true || $forceSync === true) && $email->getEmailBody() === null;
    }

    /**
     * @param Email $email
     * @param bool $forceSync
     * @param EmailOrigin $origin
     * @param EmailFolder $folder
     *
     * @return array
     *
     * @throws LoadEmailBodyFailedException
     */
    protected function loadBody(Email $email, $forceSync, $origin, $folder)
    {
        $notifications = [];
        $bodyLoaded = false;
        $emailBodyChanged = false;
        $em = $this->getManager();
        $loader = $this->getBodyLoader($origin);
        try {
            $emailBody = $loader->loadEmailBody($folder, $email, $em);
            $bodyLoaded = true;
            $em->refresh($email);
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
            if (EmailSyncNotificationAlert::ALERT_TYPE_AUTH === $notificationAlert->getAlertType()) {
                $authAlertsExist = true;
            }

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
}
