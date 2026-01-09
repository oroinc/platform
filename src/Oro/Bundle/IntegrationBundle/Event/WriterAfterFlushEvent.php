<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Doctrine\ORM\EntityManager;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after the entity manager flushes changes during import/export operations.
 *
 * This event is triggered after all pending entity changes have been persisted to the database,
 * allowing listeners to perform post-flush operations such as logging, notifications, or
 * additional data processing that depends on the flushed entities.
 */
class WriterAfterFlushEvent extends Event
{
    public const NAME = 'oro_integration.writer_after_flush';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }
}
