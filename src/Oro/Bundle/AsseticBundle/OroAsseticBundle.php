<?php

namespace Oro\Bundle\AsseticBundle;

use Symfony\Bundle\AsseticBundle\DependencyInjection\Compiler;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroAsseticBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->addResourceLoader(
            $this->getName(),
            new YamlCumulativeFileLoader('Resources/config/assets.yml')
        );
    }
}
