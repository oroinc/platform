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
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Returns job name for import
     *
     * @return string
     */
    public function getImportJobName()
    {
        return $this->job;
    }

    /**
     * Returns entity name that will be used for matching "import processor"
     *
     * @return string
     */
    public function getImportEntityFQCN()
    {
        return $this->entityName;
    }

    /**
     * Return source iterator to read from
     *
     * @return \Iterator
     */
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
    public function isAllowed(Channel $integration, array $processedConnectorsStatuses)
    {
        return $this->allowed;
    }

    /**
     * Get the order of this connector
     *
     * @return integer
     */
    public function getOrder()
    {
        return $this->order;
    }
}
