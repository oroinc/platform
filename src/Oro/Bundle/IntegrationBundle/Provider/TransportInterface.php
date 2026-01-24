<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

/**
 * Defines the contract for transport implementations that handle communication with remote systems.
 *
 * Transports are responsible for establishing connections to external integration services,
 * managing authentication, and providing the necessary configuration for the integration.
 * Each transport type is associated with a settings entity that stores transport-specific
 * configuration and a form type for configuring those settings in the UI.
 */
interface TransportInterface
{
    public function init(Transport $transportEntity);

    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel();

    /**
     * Returns form type name needed to setup transport
     *
     * @return string
     */
    public function getSettingsFormType();

    /**
     * Returns entity name needed to store transport settings
     *
     * @return string
     */
    public function getSettingsEntityFQCN();
}
