<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

interface ImportExportConfigurationProviderInterface
{
    /**
     * @return ImportExportConfigurationInterface
     */
    public function get(): ImportExportConfigurationInterface;
}
