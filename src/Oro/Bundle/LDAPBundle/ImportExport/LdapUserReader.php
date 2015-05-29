<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider;

class LdapUserReader extends IteratorBasedReader
{
    use HasChannel;

    /** @var ChannelManagerProvider */
    private $managerProvider;

    /**
     * @param ContextRegistry $contextRegistry
     * @param ConnectorContextMediator $connectorContextMediator
     * @param ChannelManagerProvider $managerProvider
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        ConnectorContextMediator $connectorContextMediator,
        ChannelManagerProvider $managerProvider
    ) {
        parent::__construct($contextRegistry);
        $this->setConnectorContextMediator($connectorContextMediator);
        $this->managerProvider = $managerProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->setContext($this->contextRegistry->getByStepExecution($stepExecution));
        parent::setStepExecution($stepExecution);
    }

    /**
     * Initializes the reader.
     */
    public function initialize()
    {
        $this->setSourceIterator(
            new \ArrayIterator($this->managerProvider->channel($this->getChannel())->findUsers())
        );
    }
}
