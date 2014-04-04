<?php

namespace Oro\Bundle\EntityConfigBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\ServiceMethodPass;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\ServiceLinkPass;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler\EntityConfigPass;

class OroEntityConfigBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->registerResource(
            $this->getName(),
            'Resources/config/entity_config.yml'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ServiceLinkPass);
        $container->addCompilerPass(new ServiceMethodPass);
        $container->addCompilerPass(new EntityConfigPass);
    }
}
