<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\Step\StepExecutionRestoreInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;
use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;
use Oro\Bundle\IntegrationBundle\Event\WriterErrorEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PersistentBatchWriter implements
    ItemWriterInterface,
    StepExecutionAwareInterface,
    StepExecutionRestoreInterface
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var ContextRegistry */
    protected $contextRegistry;

    /** @var LoggerInterface */
    protected $logger;

    /** @var StepExecution|null */
    protected $previousStepExecution;

    /**
     * @param RegistryInterface        $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param ContextRegistry          $contextRegistry
     * @param LoggerInterface          $logger
     */
    public function __construct(
        RegistryInterface $registry,
        EventDispatcherInterface $eventDispatcher,
        ContextRegistry $contextRegistry,
        LoggerInterface $logger
    ) {
        $this->registry        = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->contextRegistry = $contextRegistry;
        $this->logger          = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManager();

        try {
            $em->beginTransaction();

            $this->saveItems($items, $em);
            $em->commit();

            $configuration = $this->contextRegistry
                ->getByStepExecution($this->stepExecution)
                ->getConfiguration();

            if (empty($configuration[EntityWriter::SKIP_CLEAR])) {
                $this->doClear();
            }
        } catch (\Exception $exception) {
            $em->rollback();
            if (!$em->isOpen()) {
                $this->registry->resetManager();
            }

            $jobName = $this->stepExecution->getJobExecution()->getJobInstance()->getAlias();

            $event = new WriterErrorEvent($items, $jobName, $exception);
            $this->eventDispatcher->dispatch(WriterErrorEvent::NAME, $event);

            if ($event->getCouldBeSkipped()) {
                $importContext = $this->contextRegistry->getByStepExecution($this->stepExecution);
                $importContext->setValue(
                    'error_entries_count',
                    (int)$importContext->getValue('error_entries_count') + count($items)
                );

                $this->logger->warning($event->getWarning());

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

        $this->eventDispatcher->dispatch(WriterAfterFlushEvent::NAME, new WriterAfterFlushEvent($em));
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->previousStepExecution = $this->stepExecution;

        $this->stepExecution = $stepExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function restoreStepExecution()
    {
        $this->stepExecution = $this->previousStepExecution;
    }

    /**
     * @param array $items
     * @param EntityManager $em
     */
    protected function saveItems(array $items, EntityManager $em)
    {
        foreach ($items as $item) {
            $em->persist($item);
        }

        $em->flush();
    }

    /**
     * Clear entity manager state
     */
    protected function doClear()
    {
        $this->registry->getManager()->clear();
    }
}
