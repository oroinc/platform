<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Stub;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\AllowedConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\OrderedConnectorInterface;

class TestConnector extends AbstractConnector implements OrderedConnectorInterface, AllowedConnectorInterface
{
    public $order;
    public $type;
    public $label;
    public $job;
    public $entityName;
    public $allowed;

    /**
     * Returns type name, the same as registered in service tag
     *
     * @return string
     */
    #[\Override]
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns label for UI
     */
    #[\Override]
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Returns job name for import
     *
     * @return string
     */
    #[\Override]
    public function getImportJobName()
    {
        return $this->job;
    }

    /**
     * Returns entity name that will be used for matching "import processor"
     *
     * @return string
     */
    #[\Override]
    public function getImportEntityFQCN()
    {
        return $this->entityName;
    }

    /**
     * Return source iterator to read from
     *
     * @return \Iterator
     */
    #[\Override]
    protected function getConnectorSource()
    {
        return new TestIterator();
    }

    /**
     * @param Channel  $integration
     * @param Status[] $processedConnectorsStatuses Array of connector sync statuses which was processed before
     *
     * @return bool
     */
    #[\Override]
    public function isAllowed(Channel $integration, array $processedConnectorsStatuses)
    {
        return $this->allowed;
    }

    /**
     * Get the order of this connector
     *
     * @return integer
     */
    #[\Override]
    public function getOrder()
    {
        return $this->order;
    }
}
