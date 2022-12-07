<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Exception\DisableOriginSyncExceptionInterface;
use Oro\Bundle\EmailBundle\Exception\SyncFolderTimeoutException;
use Oro\Bundle\EmailBundle\Sync\Model\SynchronizationProcessorSettings;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Abstract class for the email synchronizer.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractEmailSynchronizer implements EmailSynchronizerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const SYNC_CODE_IN_PROCESS = 1;
    const SYNC_CODE_FAILURE    = 2;
    const SYNC_CODE_SUCCESS    = 3;
    const SYNC_CODE_IN_PROCESS_FORCE = 4;

    /** @var string */
    protected static $messageQueueTopic;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var KnownEmailAddressCheckerFactory */
    protected $knownEmailAddressCheckerFactory;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var KnownEmailAddressCheckerInterface */
    private $knownEmailAddressChecker;

    private NotificationAlertManager $notificationAlertManager;

    protected EmailSyncNotificationBag $notificationsBag;

    /** @var TokenInterface */
    private $currentToken;

    /** @var MessageProducerInterface */
    private $producer;

    /** @var string */
    protected $clearInterval = 'P1D';

    /**
     * Constructor
     */
    protected function __construct(
        ManagerRegistry $doctrine,
        KnownEmailAddressCheckerFactory $knownEmailAddressCheckerFactory,
        NotificationAlertManager $notificationAlertManager
    ) {
        $this->doctrine                        = $doctrine;
        $this->knownEmailAddressCheckerFactory = $knownEmailAddressCheckerFactory;
        $this->notificationAlertManager = $notificationAlertManager;
        $this->logger = new NullLogger();
        $this->notificationsBag = new EmailSyncNotificationBag();
    }

    public function setMessageProducer(MessageProducerInterface $producer): void
    {
        $this->producer = $producer;
    }

    public function setTokenStorage(TokenStorageInterface $tokenStorage): void
    {
        $this->tokenStorage = $tokenStorage;
        $this->currentToken = $tokenStorage->getToken();
    }

    /**
     * Returns TRUE if this class supports synchronization of the given origin.
     *
     * @param EmailOrigin $origin
     * @return bool
     */
    abstract public function supports(EmailOrigin $origin);

    /**
     * Performs a synchronization of emails for one email origin.
     * Algorithm how an email origin is selected see in findOriginToSync method.
     *
     * @param int $maxConcurrentTasks   The maximum number of synchronization jobs running in the same time
     * @param int $minExecIntervalInMin The minimum time interval (in minutes) between two synchronizations
     *                                  of the same email origin
     * @param int $maxExecTimeInMin     The maximum execution time (in minutes)
     *                                  Set -1 to unlimited
     *                                  Defaults to -1
     * @param int $maxTasks             The maximum number of email origins which can be synchronized
     *                                  Set -1 to unlimited
     *                                  Defaults to 1
     *
     * @return int
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function sync($maxConcurrentTasks, $minExecIntervalInMin, $maxExecTimeInMin = -1, $maxTasks = 1): int
    {
        if (!$this->checkConfiguration()) {
            $this->logger->info('Exit because synchronization was not configured or disabled.');
            return 0;
        }

        $startTime = $this->getCurrentUtcDateTime();
        $this->calculateClearInterval($maxExecTimeInMin);
        $this->resetHangedOrigins();

        $maxExecTimeout = $maxExecTimeInMin > 0
            ? new \DateInterval('PT' . $maxExecTimeInMin . 'M')
            : false;
        $processedOrigins = [];
        $failedOriginIds = [];
        while (true) {
            $origin = $this->findOriginToSync($maxConcurrentTasks, $minExecIntervalInMin);
            if ($origin === null) {
                $this->logger->info('Exit because nothing to synchronise.');
                break;
            }

            if (isset($processedOrigins[$origin->getId()])) {
                $this->logger->info('Exit because all origins have been synchronised.');
                break;
            }

            if ($maxExecTimeout !== false) {
                $date = $this->getCurrentUtcDateTime();
                if ($date->sub($maxExecTimeout) >= $startTime) {
                    $this->logger->info('Exit because allocated time frame elapsed.');
                    break;
                }
            }

            $processedOrigins[$origin->getId()] = true;
            $this->notificationsBag->emptyNotifications();
            try {
                $this->doSyncOrigin($origin, new SynchronizationProcessorSettings());
            } catch (SyncFolderTimeoutException $ex) {
                break;
            } catch (ORMException $ex) {
                $failedOriginIds[] = $origin->getId();
                break;
            } catch (\Exception $ex) {
                $failedOriginIds[] = $origin->getId();
            } finally {
                $this->processNotificationAlerts($origin, $this->notificationsBag->getNotifications());
            }

            if ($maxTasks > 0 && count($processedOrigins) >= $maxTasks) {
                $this->logger->info('Exit because the limit of tasks are reached.');
                break;
            }
        }

        $this->assertSyncSuccess($failedOriginIds);

        return 0;
    }

    /**
     * Performs a synchronization of emails for the given email origins.
     *
     * @param int[]                                 $originIds
     * @param SynchronizationProcessorSettings|null $settings
     *
     * @throws \Exception
     */
    public function syncOrigins(array $originIds, SynchronizationProcessorSettings $settings = null): void
    {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }

        if (!$this->checkConfiguration()) {
            $this->logger->info('Exit because synchronization was not configured.');
        }

        $failedOriginIds = [];
        foreach ($originIds as $originId) {
            $this->notificationsBag->emptyNotifications();
            $origin = $this->findOrigin($originId);
            if ($origin !== null) {
                try {
                    $this->doSyncOrigin($origin, $settings);
                } catch (SyncFolderTimeoutException $ex) {
                    break;
                } catch (\Exception $ex) {
                    $failedOriginIds[] = $origin->getId();
                } finally {
                    $this->processNotificationAlerts($origin, $this->notificationsBag->getNotifications());
                    $this->notificationsBag->emptyNotifications();
                }
            }
        }

        $this->assertSyncSuccess($failedOriginIds);
    }

    /**
     * Schedule origins sync job
     *
     * @return bool
     */
    public function supportScheduleJob()
    {
        return false;
    }

    /**
     * Schedule origins sync job
     *
     * @param int[] $originIds
     */
    public function scheduleSyncOriginsJob(array $originIds)
    {
        if (! static::$messageQueueTopic) {
            throw new \LogicException('Message queue topic is not set');
        }

        if (! $this->producer) {
            throw new \LogicException('Message producer is not set');
        }

        $this->producer->send(static::$messageQueueTopic, [
            'ids' => $originIds,
        ]);
    }

    /**
     * Checks configuration
     * This method can be used for preliminary check if the synchronization can be launched
     *
     * @return bool
     */
    protected function checkConfiguration()
    {
        return true;
    }

    /**
     * Performs a synchronization of emails for the given email origin.
     *
     * @param EmailOrigin $origin
     * @param SynchronizationProcessorSettings $settings
     *
     * @throws \Exception
     */
    protected function doSyncOrigin(EmailOrigin $origin, SynchronizationProcessorSettings $settings = null)
    {
        $this->impersonateOrganization($origin->getOrganization());
        try {
            $processor = $this->createSynchronizationProcessor($origin);
            if ($processor instanceof LoggerAwareInterface) {
                $processor->setLogger($this->logger);
            }
        } catch (DisableOriginSyncExceptionInterface $ex) {
            $this->logger->error(sprintf('Skip origin synchronization. Error: %s', $ex->getMessage()));
            $this->disableSyncForOrigin($origin);

            throw $ex;
        } catch (\Exception $ex) {
            $this->logger->error(sprintf('Skip origin synchronization. Error: %s', $ex->getMessage()));

            $this->setOriginSyncStateToFailed($origin);

            throw $ex;
        }

        try {
            $this->delegateToProcessor($origin, $processor, $settings);
        } catch (DisableOriginSyncExceptionInterface $ex) {
            $this->logger->error(sprintf('The synchronization failed. Error: %s', $ex->getMessage()));
            $this->disableSyncForOrigin($origin);

            throw $ex;
        } catch (SyncFolderTimeoutException $ex) {
            $this->logger->info($ex->getMessage());
            $this->changeOriginSyncState($origin, self::SYNC_CODE_SUCCESS);

            throw $ex;
        } catch (\Exception $ex) {
            $this->setOriginSyncStateToFailed($origin);

            $this->logger->error(
                sprintf('The synchronization failed. Error: %s', $ex->getMessage()),
                ['exception' => $ex]
            );

            throw $ex;
        }
    }

    protected function delegateToProcessor(
        EmailOrigin $origin,
        AbstractEmailSynchronizationProcessor $processor,
        SynchronizationProcessorSettings $settings = null
    ) {
        $inProcessCode = $settings && $settings->isForceMode()
            ? self::SYNC_CODE_IN_PROCESS_FORCE : self::SYNC_CODE_IN_PROCESS;
        if ($this->changeOriginSyncState($origin, $inProcessCode)) {
            $syncStartTime = $this->getCurrentUtcDateTime();
            if ($settings) {
                $processor->setSettings($settings);
            }
            $processor->process($origin, $syncStartTime, $this->notificationsBag);
            $this->changeOriginSyncState($origin, self::SYNC_CODE_SUCCESS, $syncStartTime);
        } else {
            $this->logger->info('Skip because it is already in process.');
        }
    }

    /**
     * Switches the security context to the given organization
     * Should be deleted after email sync process will be refactored
     */
    protected function impersonateOrganization(Organization $organization = null)
    {
        if ($this->currentToken === null && $organization) {
            $this->tokenStorage->setToken(
                new OrganizationToken($organization)
            );
        }
    }

    /**
     * Returns default entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        if (!$em->isOpen()) {
            $this->doctrine->resetManager();
            $em = $this->doctrine->getManager();
        }

        return $em;
    }

    /**
     * Makes sure $this->knownEmailAddressChecker initialized
     */
    protected function getKnownEmailAddressChecker()
    {
        if (!$this->knownEmailAddressChecker) {
            $this->knownEmailAddressChecker = $this->knownEmailAddressCheckerFactory->create();
            if ($this->knownEmailAddressChecker instanceof LoggerAwareInterface) {
                $this->knownEmailAddressChecker->setLogger($this->logger);
            }
        }

        return $this->knownEmailAddressChecker;
    }

    /**
     * Gets entity name implementing EmailOrigin
     *
     * @return string
     */
    abstract protected function getEmailOriginClass();

    /**
     * Creates a processor is used to synchronize emails
     *
     * @param object $origin An instance of class implementing EmailOrigin entity
     * @return AbstractEmailSynchronizationProcessor
     */
    abstract protected function createSynchronizationProcessor($origin);

    /**
     * Updates a state of the given email origin
     *
     * @return bool true if the synchronization code was updated; false if no any changes are needed
     */
    protected function changeOriginSyncState(
        EmailOrigin $origin,
        int $syncCode,
        ?\DateTime $synchronizedAt = null,
        bool $disableSync = false
    ): bool {
        $repo = $this->getEntityManager()->getRepository($this->getEmailOriginClass());
        $qb   = $repo->createQueryBuilder('o')
            ->update()
            ->set('o.syncCode', ':code')
            ->set('o.syncCodeUpdatedAt', ':updated')
            ->where('o.id = :id')
            ->setParameter('code', $syncCode)
            ->setParameter('updated', $this->getCurrentUtcDateTime(), Types::DATETIME_MUTABLE)
            ->setParameter('id', $origin->getId());

        if ($synchronizedAt !== null) {
            $qb
                ->set('o.synchronizedAt', ':synchronized')
                ->setParameter('synchronized', $synchronizedAt, Types::DATETIME_MUTABLE);
        }

        if ($syncCode === self::SYNC_CODE_IN_PROCESS || $syncCode === self::SYNC_CODE_IN_PROCESS_FORCE) {
            $qb->andWhere('(o.syncCode IS NULL OR o.syncCode <> :code)');
        }

        if ($syncCode === self::SYNC_CODE_SUCCESS) {
            $qb->set('o.syncCount', 'o.syncCount + 1');
        }

        if (true === $disableSync) {
            $qb->set('o.isSyncEnabled', ':isSyncEnabled')
                ->setParameter('isSyncEnabled', false);
        }

        $affectedRows = $qb->getQuery()->execute();

        return $affectedRows > 0;
    }

    /**
     *  Attempts to set the state of a given email origin to failed.
     */
    protected function setOriginSyncStateToFailed(EmailOrigin $origin): void
    {
        try {
            $this->changeOriginSyncState($origin, self::SYNC_CODE_FAILURE);
        } catch (\Exception $innerEx) {
            // ignore any exception here
            $this->logger->error(
                sprintf('Cannot set the fail state. Error: %s', $innerEx->getMessage()),
                ['exception' => $innerEx]
            );
        }
    }

    /**
     *  Attempts to disable origin sync and sets the state of a given email origin to failed.
     */
    protected function disableSyncForOrigin(EmailOrigin $origin): void
    {
        try {
            $this->changeOriginSyncState(
                $origin,
                self::SYNC_CODE_FAILURE,
                null,
                true
            );
        } catch (\Exception $innerEx) {
            // ignore any exception here
            $this->logger->error(
                sprintf('Cannot disable the origin sync. Error: %s', $innerEx->getMessage()),
                ['exception' => $innerEx]
            );
        }
    }

    /**
     * Finds an email origin to be synchronised
     *
     * @param int $maxConcurrentTasks   The maximum number of synchronization jobs running in the same time
     * @param int $minExecIntervalInMin The minimum time interval (in minutes) between two synchronizations
     *                                  of the same email origin
     * @return EmailOrigin
     */
    protected function findOriginToSync($maxConcurrentTasks, $minExecIntervalInMin)
    {
        $this->logger->info('Finding an email origin ...');

        $now = $this->getCurrentUtcDateTime();
        $border = clone $now;
        if ($minExecIntervalInMin > 0) {
            $border->sub(new \DateInterval('PT' . $minExecIntervalInMin . 'M'));
        }
        $min = clone $now;
        $min->sub(new \DateInterval('P1Y'));

        // time shift in minutes for fails origins
        $timeShift = 30;

        // rules:
        // - items with earlier sync code modification dates have higher priority
        // - previously failed items are shifted at 30 minutes back (it means that if sync failed
        //   the next sync is performed only after 30 minutes)
        // - "In Process" items are moved at the end
        $repo   = $this->getEntityManager()->getRepository($this->getEmailOriginClass());
        $queryBuilder = $repo->createQueryBuilder('o')
            ->select(
                'o'
                . ', CASE WHEN o.syncCode = :inProcess OR o.syncCode = :inProcessForce THEN 0 ELSE 1 END AS HIDDEN p1'
                . ', (TIMESTAMPDIFF(MINUTE, COALESCE(o.syncCodeUpdatedAt, :min), :now)'
                . ' - (CASE o.syncCode WHEN :success THEN 0 ELSE :timeShift END)) AS HIDDEN p2'
            )
            ->where('o.isActive = :isActive AND (o.syncCodeUpdatedAt IS NULL OR o.syncCodeUpdatedAt <= :border)')
            ->andWhere('(o.isSyncEnabled is NULL or o.isSyncEnabled = :isSyncEnabled)')
            ->orderBy('p1, p2 DESC, o.syncCodeUpdatedAt')
            ->setParameter('inProcess', self::SYNC_CODE_IN_PROCESS)
            ->setParameter('inProcessForce', self::SYNC_CODE_IN_PROCESS_FORCE)
            ->setParameter('isSyncEnabled', true)
            ->setParameter('success', self::SYNC_CODE_SUCCESS)
            ->setParameter('isActive', true)
            ->setParameter('now', $now, Types::DATETIME_MUTABLE)
            ->setParameter('min', $min, Types::DATETIME_MUTABLE)
            ->setParameter('border', $border, Types::DATETIME_MUTABLE)
            ->setParameter('timeShift', $timeShift)
            ->setMaxResults($maxConcurrentTasks + 1);

        $this->addOwnerFilter($queryBuilder);

        /** @var EmailOrigin[] $origins */
        $origins = $queryBuilder->getQuery()->getResult();
        $result = null;
        foreach ($origins as $origin) {
            $syncCode = $origin->getSyncCode();
            if ($syncCode !== self::SYNC_CODE_IN_PROCESS && $syncCode !== self::SYNC_CODE_IN_PROCESS_FORCE) {
                $result = $origin;
                break;
            }
        }

        if ($result === null) {
            if (!empty($origins)) {
                $this->logger->info('The maximum number of concurrent tasks is reached.');
            }
            $this->logger->info('An email origin was not found.');
        } else {
            $this->logger->info(sprintf('Found "%s" email origin. Id: %d.', (string)$result, $result->getId()));
        }

        return $result;
    }

    /**
     * Modifies QueryBuilder to filter origins by enabled owner
     */
    protected function addOwnerFilter(QueryBuilder $queryBuilder)
    {
        $expr = $queryBuilder->expr();

        $queryBuilder->leftJoin('o.owner', 'owner')
            ->andWhere(
                $expr->orX(
                    $expr->andX(
                        $expr->isNull('owner.id')
                    ),
                    $expr->andX(
                        $expr->isNotNull('owner.id'),
                        $expr->eq('owner.enabled', ':isOwnerEnabled')
                    )
                )
            )
            ->setParameter('isOwnerEnabled', true);
    }

    /**
     * Finds active email origin by its id
     *
     * @param int $originId
     * @return EmailOrigin|null
     */
    protected function findOrigin($originId)
    {
        $this->logger->info(sprintf('Finding an email origin (id: %d) ...', $originId));

        $repo  = $this->getEntityManager()->getRepository($this->getEmailOriginClass());
        $queryBuilder = $repo->createQueryBuilder('o')
            ->where('o.isActive = :isActive AND o.id = :id')
            ->andWhere('o.isSyncEnabled != :isSyncEnabled')
            ->setParameter('isActive', true)
            ->setParameter('isSyncEnabled', false)
            ->setParameter('id', $originId)
            ->setMaxResults(1);

        $this->addOwnerFilter($queryBuilder);

        $origins = $queryBuilder->getQuery()->getResult();

        /** @var EmailOrigin $result */
        $result = !empty($origins) ? $origins[0] : null;

        if ($result === null) {
            $this->logger->info('An email origin was not found.');
        } else {
            $this->logger->info(sprintf('Found "%s" email origin. Id: %d.', (string)$result, $result->getId()));
        }

        return $result;
    }

    /**
     * Marks outdated "In Process" origins as "Failure" if exist
     */
    protected function resetHangedOrigins()
    {
        $this->logger->info('Resetting hanged email origins ...');

        $now = $this->getCurrentUtcDateTime();
        $border = clone $now;
        $border->sub(new \DateInterval($this->clearInterval));

        $repo  = $this->getEntityManager()->getRepository($this->getEmailOriginClass());
        $query = $repo->createQueryBuilder('o')
            ->update()
            ->set('o.syncCode', ':failure')
            ->where('o.syncCode = :inProcess AND o.syncCodeUpdatedAt <= :border')
            ->setParameter('inProcess', self::SYNC_CODE_IN_PROCESS)
            ->setParameter('failure', self::SYNC_CODE_FAILURE)
            ->setParameter('border', $border, Types::DATETIME_MUTABLE)
            ->getQuery();

        $affectedRows = $query->execute();
        $this->logger->info(sprintf('Updated %d row(s).', $affectedRows));
    }

    /**
     * Gets a DateTime object that is set to the current date and time in UTC.
     *
     * @return \DateTime
     */
    protected function getCurrentUtcDateTime()
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }

    protected function calculateClearInterval($maxExecTimeInMin)
    {
        if ($maxExecTimeInMin > 5) {
            $this->clearInterval = 'PT' . ($maxExecTimeInMin * 5) . 'M';
        }
    }

    /**
     * @throws \Exception
     */
    private function assertSyncSuccess(array $failedOriginIds)
    {
        if ($failedOriginIds) {
            throw new \Exception(
                sprintf(
                    'The email synchronization failed for the following origins: %s.',
                    implode(', ', $failedOriginIds)
                )
            );
        }
    }

    private function processNotificationAlerts(EmailOrigin $origin, array $notificationAlerts): void
    {
        $userId = $origin->getOwner()?->getId();
        $organizationId = $origin->getOrganization()->getId();
        $originId = $origin->getId();

        $authAlertsExist = false;
        $collectionAlertsExist = false;
        $failedFoldersExist = false;
        /** @var $notificationAlert EmailSyncNotificationAlert */
        foreach ($notificationAlerts as $notificationAlert) {
            if (EmailSyncNotificationAlert::ALERT_TYPE_AUTH === $notificationAlert->getAlertType()) {
                $authAlertsExist = true;
            }
            if (EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER === $notificationAlert->getAlertType()) {
                $failedFoldersExist = true;
            }
            if (EmailSyncNotificationAlert::ALERT_TYPE_SYNC === $notificationAlert->getAlertType()
                && EmailSyncNotificationAlert::STEP_GET_LIST === $notificationAlert->getStep()
            ) {
                $collectionAlertsExist = true;
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

            if (false === $collectionAlertsExist) {
                $this->notificationAlertManager->resolveNotificationAlertsByAlertTypeAndStepForUserAndOrganization(
                    EmailSyncNotificationAlert::ALERT_TYPE_SYNC,
                    EmailSyncNotificationAlert::STEP_GET_LIST,
                    $userId,
                    $organizationId
                );
            }

            if (false === $failedFoldersExist) {
                $this->notificationAlertManager->resolveNotificationAlertsByAlertTypeForUserAndOrganization(
                    EmailSyncNotificationAlert::ALERT_TYPE_SWITCH_FOLDER,
                    $userId,
                    $organizationId
                );
            }
        }

        $this->notificationsBag->emptyNotifications();
    }
}
