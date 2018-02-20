<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Sync\Model\SynchronizationProcessorSettings;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

interface EmailSynchronizerInterface
{
    /**
     * @param MessageProducerInterface $producer
     */
    public function setMessageProducer(MessageProducerInterface $producer);

    /**
     * @param TokenStorage $tokenStorage
     */
    public function setTokenStorage(TokenStorage $tokenStorage);

    /**
     * Returns TRUE if this class supports synchronization of the given origin.
     *
     * @param EmailOrigin $origin
     *
     * @return bool
     */
    public function supports(EmailOrigin $origin);

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
     */
    public function sync($maxConcurrentTasks, $minExecIntervalInMin, $maxExecTimeInMin = -1, $maxTasks = 1);

    /**
     * Performs a synchronization of emails for the given email origins.
     *
     * @param int[] $originIds
     * @param SynchronizationProcessorSettings $settings
     *
     * @throws \Exception
     */
    public function syncOrigins(array $originIds, SynchronizationProcessorSettings $settings = null);

    /**
     * Schedule origins sync job
     *
     * @return bool
     */
    public function supportScheduleJob();

    /**
     * Schedule origins sync job
     *
     * @param int[] $originIds
     */
    public function scheduleSyncOriginsJob(array $originIds);
}
