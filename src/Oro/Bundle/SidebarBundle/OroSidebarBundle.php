<?php

namespace Oro\Bundle\SidebarBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroSidebarBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->addResourceLoader(
            $this->getName(),
            new FolderingCumulativeFileLoader(
                '{folder}',
                '\w+',
                new YamlCumulativeFileLoader('Resources/public/sidebar_widgets/{folder}/widget.yml')
            )
        );
    }
}
