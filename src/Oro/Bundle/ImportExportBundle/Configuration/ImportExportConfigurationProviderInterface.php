<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

interface ImportExportConfigurationProviderInterface
{
    public function get(): ImportExportConfigurationInterface;
}
