<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Psr\Log\LoggerAwareInterface;

use Symfony\Component\HttpFoundation\ParameterBag;

use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;

use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;

abstract class AbstractConnector extends IteratorBasedReader implements ConnectorInterface, StepExecutionAwareInterface
{
    /** @var TransportInterface */
    protected $transport;

    /** @var Integration */
    protected $channel;

    /** @var ParameterBag */
    protected $transportSettings;

    /** @var LoggerStrategy */
    protected $logger;

    /** @var ConnectorContextMediator */
    protected $contextMediator;

    /**
     * @param ContextRegistry          $contextRegistry
     * @param LoggerStrategy           $logger
     * @param ConnectorContextMediator $contextMediator
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        LoggerStrategy $logger,
        ConnectorContextMediator $contextMediator
    ) {
        parent::__construct($contextRegistry);
        $this->logger          = $logger;
        $this->contextMediator = $contextMediator;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->transport = $this->contextMediator->getTransport($context, true);
        $this->channel   = $this->contextMediator->getChannel($context);

        $this->validateConfiguration();
        $this->transport->init($this->channel->getTransport());
        $this->setSourceIterator($this->getConnectorSource());

        if ($this->getSourceIterator() instanceof LoggerAwareInterface) {
            $this->getSourceIterator()->setLogger($this->logger);
        }
    }

    /**
     * Validates initialization
     * Basically added to be overridden in child classes
     *
     * @throws LogicException
     */
    protected function validateConfiguration()
    {
        if (!$this->transport instanceof TransportInterface) {
            throw new LogicException('Could not retrieve transport from context');
        }
    }

    /**
     * Returns whether connector supports force sync or no
     *
     * @return bool
     */
    protected function supportsForceSync()
    {
        return false;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    protected function addStatusData($key, $value)
    {
        $context    = $this->getStepExecution()->getExecutionContext();
        $data       = $context->get(ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY) ? : [];
        $data[$key] = $value;

        $context->put(ConnectorInterface::CONTEXT_CONNECTOR_DATA_KEY, $data);
    }

    /**
     * Return source iterator to read from
     *
     * @return \Iterator
     */
    abstract protected function getConnectorSource();
}
