<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

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
     * @param ProcessFactory $factory
     * @param ProcessLogger $logger
     */
    public function __construct(ProcessFactory $factory, ProcessLogger $logger)
    {
        $this->factory = $factory;
        $this->logger = $logger;
    }

    /**
     * @param ProcessTrigger $processTrigger
     * @param ProcessData $processData
     * @throws InvalidParameterException
     */
    public function handleTrigger(ProcessTrigger $processTrigger, ProcessData $processData)
    {
        if (!$processData['data']) {
            throw new InvalidParameterException('Invalid process data. Entity can not be empty.');
        }

        $process = $this->factory->create($processTrigger->getDefinition());
        $process->execute($processData);

        $this->logger->debug('Process executed', $processTrigger, $processData);
    }

    /**
     * @param ProcessJob $processJob
     */
    public function handleJob(ProcessJob $processJob)
    {
        $this->handleTrigger($processJob->getProcessTrigger(), $processJob->getData());
    }
}
