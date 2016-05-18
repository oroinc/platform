<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Exception\SyncFolderTimeoutException;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractEmailSynchronizer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const SYNC_CODE_IN_PROCESS = 1;
    const SYNC_CODE_FAILURE    = 2;
    const SYNC_CODE_SUCCESS    = 3;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var KnownEmailAddressCheckerFactory */
    protected $knownEmailAddressCheckerFactory;

    /** @var TokenStorage */
    protected $tokenStorage;

    /** @var KnownEmailAddressCheckerInterface */
    private $knownEmailAddressChecker;

    /** @var TokenInterface */
    private $currentToken;

    /**
     * Constructor
     *
     * @param ManagerRegistry                 $doctrine
     * @param KnownEmailAddressCheckerFactory $knownEmailAddressCheckerFactory
     */
    protected function __construct(
        ManagerRegistry $doctrine,
        KnownEmailAddressCheckerFactory $knownEmailAddressCheckerFactory
    ) {
        $this->doctrine                        = $doctrine;
        $this->knownEmailAddressCheckerFactory = $knownEmailAddressCheckerFactory;
    }

    /**
     * @param TokenStorage $tokenStorage
     */
    public function setTokenStorage(TokenStorage $tokenStorage)
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
     * @return int
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function sync($maxConcurrentTasks, $minExecIntervalInMin, $maxExecTimeInMin = -1, $maxTasks = 1)
    {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }

        if (!$this->checkConfiguration()) {
            $this->logger->info('Exit because synchronization was not configured or disabled.');
            return 0;
        }

        $startTime = $this->getCurrentUtcDateTime();
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
            try {
                $this->doSyncOrigin($origin);
            } catch (SyncFolderTimeoutException $ex) {
                break;
            } catch (\Exception $ex) {
                $failedOriginIds[] = $origin->getId();
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
     * @param int[] $originIds
     * @throws \Exception
     */
    public function syncOrigins(array $originIds)
    {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }

        if (!$this->checkConfiguration()) {
            $this->logger->info('Exit because synchronization was not configured.');
        }

        $failedOriginIds = [];
        foreach ($originIds as $originId) {
            $origin = $this->findOrigin($originId);
            if ($origin !== null) {
                try {
                    $this->doSyncOrigin($origin);
                } catch (SyncFolderTimeoutException $ex) {
                    break;
                } catch (\Exception $ex) {
                    $failedOriginIds[] = $origin->getId();
                }
            }
        }

        $this->assertSyncSuccess($failedOriginIds);
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
     * @throws \Exception
     */
    protected function doSyncOrigin(EmailOrigin $origin)
    {
        $this->impersonateOrganization($origin->getOrganization());
        try {
            $processor = $this->createSynchronizationProcessor($origin);
            if ($processor instanceof LoggerAwareInterface) {
                $processor->setLogger($this->logger);
            }
        } catch (\Exception $ex) {
            $this->logger->error(sprintf('Skip origin synchronization. Error: %s', $ex->getMessage()));

            throw $ex;
        }

        try {
            if ($this->changeOriginSyncState($origin, self::SYNC_CODE_IN_PROCESS)) {
                $syncStartTime = $this->getCurrentUtcDateTime();
                $processor->process($origin, $syncStartTime);
                $this->changeOriginSyncState($origin, self::SYNC_CODE_SUCCESS, $syncStartTime);
            } else {
                $this->logger->info('Skip because it is already in process.');
            }
        } catch (SyncFolderTimeoutException $ex) {
            $this->logger->info($ex->getMessage());
            $this->changeOriginSyncState($origin, self::SYNC_CODE_SUCCESS);

            throw $ex;
        } catch (\Exception $ex) {
            try {
                $this->changeOriginSyncState($origin, self::SYNC_CODE_FAILURE);
            } catch (\Exception $innerEx) {
                // ignore any exception here
                $this->logger->error(
                    sprintf('Cannot set the fail state. Error: %s', $innerEx->getMessage()),
                    ['exception' => $innerEx]
                );
            }

            $this->logger->error(
                sprintf('The synchronization failed. Error: %s', $ex->getMessage()),
                ['exception' => $ex]
            );

            throw $ex;
        }
    }

    /**
     * Switches the security context to the given organization
     * @todo: Should be deleted after email sync process will be refactored
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
     * @param EmailOrigin $origin
     * @param int $syncCode Can be one of self::SYNC_CODE_* constants
     * @param \DateTime|null $synchronizedAt
     * @return bool true if the synchronization code was updated; false if no any changes are needed
     */
    protected function changeOriginSyncState(EmailOrigin $origin, $syncCode, $synchronizedAt = null)
    {
        $repo = $this->getEntityManager()->getRepository($this->getEmailOriginClass());
        $qb   = $repo->createQueryBuilder('o')
            ->update()
            ->set('o.syncCode', ':code')
            ->set('o.syncCodeUpdatedAt', ':updated')
            ->where('o.id = :id')
            ->setParameter('code', $syncCode)
            ->setParameter('updated', $this->getCurrentUtcDateTime())
            ->setParameter('id', $origin->getId());

        if ($synchronizedAt !== null) {
            $qb
                ->set('o.synchronizedAt', ':synchronized')
                ->setParameter('synchronized', $synchronizedAt);
        }

        if ($syncCode === self::SYNC_CODE_IN_PROCESS) {
            $qb->andWhere('(o.syncCode IS NULL OR o.syncCode <> :code)');
        }

        if ($syncCode === self::SYNC_CODE_SUCCESS) {
            $qb->set('o.syncCount', 'o.syncCount + 1');
        }

        $affectedRows = $qb->getQuery()->execute();

        return $affectedRows > 0;
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

        // rules:
        // - items with earlier sync code modification dates have higher priority
        // - previously failed items are shifted at 30 minutes back (it means that if sync failed
        //   the next sync is performed only after 30 minutes)
        // - "In Process" items are moved at the end
        $repo   = $this->getEntityManager()->getRepository($this->getEmailOriginClass());
        $query = $repo->createQueryBuilder('o')
            ->select(
                'o'
                . ', CASE WHEN o.syncCode = :inProcess THEN 0 ELSE 1 END AS HIDDEN p1'
                . ', (COALESCE(o.syncCode, 1000) * 30'
                . ' + TIMESTAMPDIFF(MINUTE, COALESCE(o.syncCodeUpdatedAt, :min), :now)'
                . ' / (CASE o.syncCode WHEN :success THEN 100 ELSE 1 END)) AS HIDDEN p2'
            )
            ->where('o.isActive = :isActive AND (o.syncCodeUpdatedAt IS NULL OR o.syncCodeUpdatedAt <= :border)')
            ->orderBy('p1, p2 DESC, o.syncCodeUpdatedAt')
            ->setParameter('inProcess', self::SYNC_CODE_IN_PROCESS)
            ->setParameter('success', self::SYNC_CODE_SUCCESS)
            ->setParameter('isActive', true)
            ->setParameter('now', $now)
            ->setParameter('min', $min)
            ->setParameter('border', $border)
            ->setMaxResults($maxConcurrentTasks + 1)
            ->getQuery();

        /** @var EmailOrigin[] $origins */
        $origins = $query->getResult();
        $result = null;
        foreach ($origins as $origin) {
            if ($origin->getSyncCode() !== self::SYNC_CODE_IN_PROCESS) {
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
     * Finds active email origin by its id
     *
     * @param int $originId
     * @return EmailOrigin|null
     */
    protected function findOrigin($originId)
    {
        $this->logger->info(sprintf('Finding an email origin (id: %d) ...', $originId));

        $repo  = $this->getEntityManager()->getRepository($this->getEmailOriginClass());
        $query = $repo->createQueryBuilder('o')
            ->where('o.isActive = :isActive AND o.id = :id')
            ->setParameter('isActive', true)
            ->setParameter('id', $originId)
            ->setMaxResults(1)
            ->getQuery();
        $origins = $query->getResult();

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
        $border->sub(new \DateInterval('P1D'));

        $repo  = $this->getEntityManager()->getRepository($this->getEmailOriginClass());
        $query = $repo->createQueryBuilder('o')
            ->update()
            ->set('o.syncCode', ':failure')
            ->where('o.syncCode = :inProcess AND o.syncCodeUpdatedAt <= :border')
            ->setParameter('inProcess', self::SYNC_CODE_IN_PROCESS)
            ->setParameter('failure', self::SYNC_CODE_FAILURE)
            ->setParameter('border', $border)
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

    /**
     * @param $failedOriginIds
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
}
