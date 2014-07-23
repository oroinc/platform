<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Writer;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

class PersistentBatchWriter implements ItemWriterInterface
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var EntityManager */
    protected $em;

    protected $eventDispatcher;

    /**
     * @param RegistryInterface        $registry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(RegistryInterface $registry, EventDispatcherInterface $eventDispatcher)
    {
        $this->registry        = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $this->ensureEntityManagerReady();

        try {
            $this->em->beginTransaction();

            foreach ($items as $item) {
                $this->em->persist($item);
            }

            $this->em->flush();

            $this->em->commit();
            $this->em->clear();
        } catch (\Exception $exception) {
            $this->em->rollback();

            throw $exception;
        }

        $this->eventDispatcher->dispatch(WriterAfterFlushEvent::NAME, new WriterAfterFlushEvent($this->em));
    }

    /**
     * Prepares EntityManager, reset it if closed with error
     */
    protected function ensureEntityManagerReady()
    {
        $this->em = $this->registry->getManager();

        if (!$this->em->isOpen()) {
            $this->registry->resetManager();
            $this->ensureEntityManagerReady();
        }
    }
}
