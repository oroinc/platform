<?php

namespace Oro\Bundle\CacheBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;

class OroCacheBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->clear();
    }

    /**
     * Initializes the bundle.
     *
     * It is called after a list of bundles is loaded but before the DI container is initialized.
     *
     * @param KernelInterface $kernel
     */
    public function init(KernelInterface $kernel)
    {
        $bundles       = [];
        $bundleObjects = $kernel->getBundles();
        foreach ($bundleObjects as $name => $bundle) {
            $bundles[$name] = get_class($bundle);
        }
        CumulativeResourceManager::getInstance()->setBundles($bundles);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CacheConfigurationPass());
    }
}
