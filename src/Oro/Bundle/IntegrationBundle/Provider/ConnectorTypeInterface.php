<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface ConnectorTypeInterface
{
    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel();

    /**
     * Returns form type name needed to setup connector
     *
     * @return string
     */
    public function getSettingsFormType();

    /**
     * Returns entity name needed to store connector settings
     *
     * @return string
     */
    public function getSettingsEntityFQCN();
}
