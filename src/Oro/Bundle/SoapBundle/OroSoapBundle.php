<?php

namespace Oro\Bundle\SoapBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\SoapBundle\DependencyInjection\Compiler\LoadPass;

class OroSoapBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->registerResource(
            $this->getName(),
            'Resources/config/oro/soap.yml'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new LoadPass());
    }
}
