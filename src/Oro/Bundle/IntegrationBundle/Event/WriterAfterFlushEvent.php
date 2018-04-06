<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\Event;

class WriterAfterFlushEvent extends Event
{
    const NAME = 'oro_integration.writer_after_flush';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
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
