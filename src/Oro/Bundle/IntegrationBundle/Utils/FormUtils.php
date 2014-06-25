<?php

namespace Oro\Bundle\IntegrationBundle\Utils;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

class FormUtils
{
    /** @var TypesRegistry */
    protected $registry;

    /**
     * @param TypesRegistry $registry
     */
    public function __construct(TypesRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Checks whether integration has at least on connector that supports backward sync
     *
     * @param string $integrationType
     *
     * @return bool
     */
    public function hasTwoWaySyncConnectors($integrationType)
    {
        $connectors = $this->registry->getRegisteredConnectorsTypes(
            $integrationType,
            function (ConnectorInterface $connector) {
                return $connector instanceof TwoWaySyncConnectorInterface;
            }
        );

        return !$connectors->isEmpty();
    }

    /**
     * Return true if integration was synced at least once
     *
     * @param Integration $integration
     *
     * @return bool
     */
    public static function wasSyncedAtLeastOnce(Integration $integration)
    {
        return $integration->getStatuses()->exists(
            function ($key, Status $status) {
                return intval($status->getCode()) === Status::STATUS_COMPLETED;
            }
        );
    }
}
