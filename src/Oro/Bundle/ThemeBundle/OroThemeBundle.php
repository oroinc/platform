<?php

namespace Oro\Bundle\ThemeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroThemeBundle extends Bundle
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
                new YamlCumulativeFileLoader('Resources/public/themes/{folder}/settings.yml')
            )
        );
    }
}
