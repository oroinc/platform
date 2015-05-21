<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\LDAP\Factory\LdapManagerFactory;

class LdapUserReader extends IteratorBasedReader
{
    /** @var ConnectorContextMediator */
    private $connectorContextMediator;

    /** @var LdapManagerFactory */
    protected $ldapManagerFactory;

    /** @var Channel */
    protected $channel;

    public function __construct(
        ConnectorContextMediator $connectorContextMediator,
        ContextRegistry $contextRegistry,
        LdapManagerFactory $ldapManagerFactory
    ) {
        parent::__construct($contextRegistry);

        $this->ldapManagerFactory = $ldapManagerFactory;
        $this->connectorContextMediator = $connectorContextMediator;
    }

    /**
     * Initializes the reader.
     */
    public function initialize()
    {
        $this->setSourceIterator(new \ArrayIterator(
            $this->ldapManagerFactory->getInstanceForChannel($this->getChannel())->findUsers()
        ));
    }


    /**
     * Returns integration channel.
     *
     * @return Channel
     */
    protected function getChannel()
    {
        if ($this->channel === null || $this->getContext()->getOption('channel') !== $this->channel->getId()) {
            $this->channel = $this->connectorContextMediator->getChannel($this->getContext());
        }

        return $this->channel;
    }
}