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

    /**
     * Returns entity name that will be used for matching "import processor"
     *
     * @return string
     */
    public function getImportEntityFQCN();

    /**
     * Returns job name for import
     *
     * @param bool $isValidationOnly
     *
     * @return string
     */
    public function getImportJobName($isValidationOnly = false);
}
