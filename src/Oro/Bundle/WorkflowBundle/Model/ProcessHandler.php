<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Event\ProcessEvents;
use Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent;
use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProcessHandler
{
    /**
     * @var ProcessFactory
     */
    protected $factory;

    /**
     * @var ProcessLogger
     */
    protected $logger;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $processes = [];

    public function __construct(
        ProcessFactory $factory,
        ProcessLogger $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->factory = $factory;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws InvalidParameterException
     */
    public function handleTrigger(ProcessTrigger $processTrigger, ProcessData $processData)
    {
        $this->eventDispatcher->dispatch(
            new ProcessHandleEvent($processTrigger, $processData),
            ProcessEvents::HANDLE_BEFORE
        );

        $process = $this->getProcess($processTrigger);
        $process->execute($processData);

        $this->logger->debug('Process executed', $processTrigger, $processData);

        $this->eventDispatcher->dispatch(
            new ProcessHandleEvent($processTrigger, $processData),
            ProcessEvents::HANDLE_AFTER
        );
    }

    public function handleJob(ProcessJob $processJob)
    {
        $this->handleTrigger($processJob->getProcessTrigger(), $processJob->getData());
    }

    public function finishTrigger(ProcessTrigger $processTrigger, ProcessData $processData)
    {
        $this->eventDispatcher->dispatch(
            new ProcessHandleEvent($processTrigger, $processData),
            ProcessEvents::HANDLE_AFTER_FLUSH
        );
    }

    public function finishJob(ProcessJob $processJob)
    {
        $this->finishTrigger($processJob->getProcessTrigger(), $processJob->getData());
    }

    /**
     * @param ProcessTrigger $processTrigger
     * @param ProcessData $processData
     * @return bool
     */
    public function isTriggerApplicable(ProcessTrigger $processTrigger, ProcessData $processData)
    {
        $process = $this->getProcess($processTrigger);

        return $process->isApplicable($processData);
    }

    /**
     * @param ProcessTrigger $processTrigger
     * @return Process
     */
    protected function getProcess(ProcessTrigger $processTrigger)
    {
        if (!array_key_exists($processTrigger->getId(), $this->processes)) {
            $this->processes[$processTrigger->getId()] = $this->factory->create($processTrigger->getDefinition());
        }

        return $this->processes[$processTrigger->getId()];
    }
}
