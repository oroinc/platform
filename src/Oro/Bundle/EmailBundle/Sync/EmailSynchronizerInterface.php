<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Sync\Model\SynchronizationProcessorSettings;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Interface which must be implemented by email synchronizer.
 */
interface EmailSynchronizerInterface
{
    public function setMessageProducer(MessageProducerInterface $producer): void;

    public function setTokenStorage(TokenStorageInterface $tokenStorage): void;

    /**
     * Returns TRUE if this class supports synchronization of the given origin.
     */
    public function supports(EmailOrigin $origin): bool;

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
     * @throws \Exception
     */
    public function sync(
        int $maxConcurrentTasks,
        int $minExecIntervalInMin,
        int $maxExecTimeInMin = -1,
        int $maxTasks = 1
    ): int;

    /**
     * Performs a synchronization of emails for the given email origins.
     *
     * @param int[] $originIds
     * @param SynchronizationProcessorSettings|null $settings
     *
     * @throws \Exception
     */
    public function syncOrigins(array $originIds, SynchronizationProcessorSettings $settings = null): void;

    /**
     * Schedule origins sync job
     */
    public function supportScheduleJob(): bool;

    /**
     * Schedule origins sync job
     *
     * @param int[] $originIds
     */
    public function scheduleSyncOriginsJob(array $originIds): void;
}
