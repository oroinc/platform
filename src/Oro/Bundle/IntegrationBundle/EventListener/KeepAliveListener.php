<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Job\DoctrineJobRepository;
use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;

/**
 * Keeps alive DB connection for really long integration processes.
 */
class KeepAliveListener
{
    /**
     * @var DoctrineJobRepository
     */
    protected $batchJobRepository;

    public function __construct(DoctrineJobRepository $batchJobRepository)
    {
        $this->batchJobRepository = $batchJobRepository;
    }

    public function onWriterAfterFlush(WriterAfterFlushEvent $event)
    {
        // keep alive connection for really long integration processes
        $dql = 'SELECT e.id FROM ' . JobExecution::class . ' e WHERE e.id = 1';
        $this->batchJobRepository->getJobManager()->createQuery($dql)->execute();
    }
}
