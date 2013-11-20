<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface TransportTypeInterface
{
    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel();

    public function getSettingsFormType();

    public function getSettingsEntityFQCN();
}
