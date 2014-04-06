<?php

namespace Oro\Bundle\ConfigBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\ConfigPass;
use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationPass;

use Oro\Component\Config\CumulativeResourceManager;

class OroConfigBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->registerResource(
            $this->getName(),
            'Resources/config/system_configuration.yml'
        );
        CumulativeResourceManager::getInstance()->registerResource(
            'entity_output',
            'Resources/config/entity_output.yml'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigPass());
        $container->addCompilerPass(new SystemConfigurationPass());
    }
}
