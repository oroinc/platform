<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\LDAP\Factory\LdapManagerFactory;
use Oro\Bundle\LDAPBundle\LDAP\LdapChannelManager;

class LdapUserReader extends IteratorBasedReader
{
    use HasChannel;

    /** @var LdapChannelManager */
    private $channelManager;

    public function __construct(
        ContextRegistry $contextRegistry,
        ConnectorContextMediator $connectorContextMediator,
        LdapChannelManager $channelManager
    ) {
        parent::__construct($contextRegistry);
        $this->setConnectorContextMediator($connectorContextMediator);
        $this->channelManager = $channelManager;
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
        $this->setSourceIterator(new \ArrayIterator($this->channelManager->findUsers($this->getChannel())));
    }
}