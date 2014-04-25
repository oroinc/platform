<?php

namespace Oro\Bundle\EmailBundle\Sync;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

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
     */
    public function syncOrigins($origins)
    {
        /** @var AbstractEmailSynchronizer[] $synchronizers */
        $synchronizers = [];
        foreach ($this->synchronizerServiceIds as $serviceId) {
            $synchronizers[] = $this->container->get($serviceId);
        }

        foreach ($synchronizers as $synchronizer) {
            $supportedOriginIds = [];
            foreach ($origins as $origin) {
                if ($synchronizer->supports($origin)) {
                    $supportedOriginIds[] = $origin->getId();
                }
            }
            if (!empty($supportedOriginIds)) {
                $synchronizer->syncOrigins($supportedOriginIds);
            }
        }
    }
}
