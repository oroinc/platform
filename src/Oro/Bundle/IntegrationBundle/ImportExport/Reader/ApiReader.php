<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Reader;

use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;
use Oro\Bundle\ImportExportBundle\Reader\ReaderInterface;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

class ApiReader extends AbstractReader implements ReaderInterface, StepExecutionAwareInterface
{
    /** @var \Closure */
    protected $loggerClosure;

    /** @var ConnectorInterface */
    protected $connector;

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->loggerClosure = $context->getOption('logger');
        $this->connector     = $context->getOption('connector');
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        // read peace of data, skipping empty
        do {
            $data = $this->connector->read();
        } while ($data === false);

        if (is_null($data)) {
            return null; // no data anymore
        }

        $context = $this->getContext();
        $context->incrementReadCount();
        $context->incrementReadOffset();

        // customer connector knows how to advance
        // batch counter/boundaries to the next ones

        if (is_callable($this->loggerClosure)) {
            call_user_func($this->loggerClosure, "Reading item...");
        }

        return $data;
    }
}
