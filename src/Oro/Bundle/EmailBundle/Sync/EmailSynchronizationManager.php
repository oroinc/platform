<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailSynchronizationManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string[]
     */
    protected $synchronizerServiceIds;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Add email synchronizer
     *
     * @param string $synchronizerServiceId
     */
    public function addSynchronizer($synchronizerServiceId)
    {
        $this->synchronizerServiceIds[] = $synchronizerServiceId;
    }

    /**
     * Performs a synchronization of emails for the given email origins.
     *
     * @param EmailOrigin[] $origins
     * @param bool          $scheduleJob
     */
    public function syncOrigins($origins, $scheduleJob = false)
    {
        /** @var AbstractEmailSynchronizer[] $synchronizers */
        $synchronizers = [];
        foreach ($this->synchronizerServiceIds as $serviceId) {
            $synchronizers[] = $this->container->get($serviceId);
        }

        foreach ($synchronizers as $synchronizer) {
            $this->performSync($origins, $synchronizer, $scheduleJob);
        }
    }

    /**
     * @param EmailOrigin[]             $origins
     * @param AbstractEmailSynchronizer $synchronizer
     * @param bool                      $scheduleJob
     */
    protected function performSync($origins, $synchronizer, $scheduleJob)
    {
        $supportedOriginIds = [];
        foreach ($origins as $origin) {
            if ($synchronizer->supports($origin)) {
                $supportedOriginIds[] = $origin->getId();
            }
        }
        if ($scheduleJob && $synchronizer->supportScheduleJob()) {
            // create job if not exists yet
            $synchronizer->scheduleSyncOriginsJob($supportedOriginIds);
        } else {
            if (0 !== count($supportedOriginIds)) {
                $synchronizer->syncOrigins($supportedOriginIds);
            }
        }
    }
}
