<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Exception\InvalidItemException;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionRestoreInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;
use Oro\Bundle\IntegrationBundle\Event\WriterAfterFlushEvent;
use Oro\Bundle\IntegrationBundle\Event\WriterErrorEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Persists supplied data to the database within a transaction
 */
class PersistentBatchWriter implements
    ItemWriterInterface,
    StepExecutionAwareInterface,
    StepExecutionRestoreInterface
{
    /** @var ManagerRegistry */
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

    public function __construct(
        ManagerRegistry $registry,
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
            $this->eventDispatcher->dispatch($event, WriterErrorEvent::NAME);

            if ($event->getCouldBeSkipped()) {
                $importContext = $this->contextRegistry->getByStepExecution($this->stepExecution);
                $importContext->incrementErrorEntriesCount(count($items));

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

        $this->eventDispatcher->dispatch(new WriterAfterFlushEvent($em), WriterAfterFlushEvent::NAME);
    }

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
