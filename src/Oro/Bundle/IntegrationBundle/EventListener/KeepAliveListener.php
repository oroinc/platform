<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository;
use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;

class KeepAliveListener
{
    /**
     * @var DoctrineJobRepository
     */
    protected $batchJobRepository;

    /**
     * @param DoctrineJobRepository $batchJobRepository
     */
    public function __construct(DoctrineJobRepository $batchJobRepository)
    {
        $this->batchJobRepository = $batchJobRepository;
    }

    /**
     * @param WriterAfterFlushEvent $event
     */
    public function onWriterAfterFlush(WriterAfterFlushEvent $event)
    {
        // keep alive connection for really long integration processes
        $dql = 'SELECT e.id FROM AkeneoBatchBundle:JobExecution e WHERE e.id = 1';
        $this->batchJobRepository->getJobManager()->createQuery($dql)->execute();
    }
}
