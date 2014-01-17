<?php

namespace Oro\Bundle\IntegrationBundle\Provider;


interface ConnectorInterface
{
    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel();

    /**
     * Returns entity name that will be used for matching "import processor"
     *
     * @return string
     */
    public function getImportEntityFQCN();

    /**
     * Returns job name for import
     *
     * @return string
     */
    public function getImportJobName();
}
