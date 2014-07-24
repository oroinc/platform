<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Writer;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Event\WriterErrorEvent;
use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

class PersistentBatchWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var EntityManager */
    protected $em;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var StepExecution */
    protected $stepExecution;

    /**
     * @param RegistryInterface        $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param ContextRegistry          $contextRegistry
     */
    public function __construct(
        RegistryInterface $registry,
        EventDispatcherInterface $eventDispatcher,
        ContextRegistry $contextRegistry
    ) {
        $this->registry        = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->contextRegistry = $contextRegistry;
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

            $jobName = $this->stepExecution->getJobExecution()->getJobInstance()->getAlias();

            $event = new WriterErrorEvent($items, $jobName, $exception);
            $this->eventDispatcher->dispatch(WriterErrorEvent::NAME, $event);

            if ($event->getCouldBeSkipped()) {
                $importContext = $this->contextRegistry->getByStepExecution($this->stepExecution);
                $importContext->setValue(
                    'error_entries_count',
                    (int)$importContext->getValue('error_entries_count') + count($items)
                );
                $importContext->addError($event->getWarning());

                if ($event->getException() === $exception) {
                    // exception are already handled and job can move forward
                    throw new InvalidItemException($event->getWarning(), []);
                } else {
                    // exception are already created and ready to be rethrown
                    throw $event->getException();
                }
            } else {
                throw $exception;
            }
        }

        $this->eventDispatcher->dispatch(WriterAfterFlushEvent::NAME, new WriterAfterFlushEvent($this->em));
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
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
