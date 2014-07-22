<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Writer;

use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;

class PersistentBatchWriter extends EntityWriter
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        try {
            $this->entityManager->beginTransaction();
            foreach ($items as $item) {
                $this->entityManager->persist($item);
            }
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->eventDispatcher->dispatch(WriterAfterFlushEvent::NAME, new WriterAfterFlushEvent($this->entityManager));
    }
}
