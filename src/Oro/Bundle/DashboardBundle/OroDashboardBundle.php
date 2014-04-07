<?php

namespace Oro\Bundle\DashboardBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroDashboardBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->addResourceLoader(
            $this->getName(),
            new YamlCumulativeFileLoader('Resources/config/dashboard.yml')
        );
    }
}
