<?php

namespace Oro\Bundle\AsseticBundle;

use Symfony\Bundle\AsseticBundle\DependencyInjection\Compiler;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Component\Config\CumulativeResourceManager;

class OroAsseticBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->registerResource(
            $this->getName(),
            'Resources/config/assets.yml'
        );
    }
}
