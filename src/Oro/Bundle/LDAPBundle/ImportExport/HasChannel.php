<?php
namespace Oro\Bundle\LDAPBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;

trait HasChannel
{
    /** @var Channel */
    private $channel = null;

    /** @var ContextInterface */
    private $context = null;

    /** @var ConnectorContextMediator */
    private $connectorContextMediator;

    /**
     * @param ContextInterface $context
     *
     * @return $this
     */
    protected function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return ContextInterface
     */
    protected function getContext()
    {
        if ($this->context == null) {
            throw new \LogicException('Context must be set.');
        }

        return $this->context;
    }

    /**
     * @param ConnectorContextMediator $connectorContextMediator
     *
     * @return $this
     */
    protected function setConnectorContextMediator($connectorContextMediator)
    {
        $this->connectorContextMediator = $connectorContextMediator;

        return $this;
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