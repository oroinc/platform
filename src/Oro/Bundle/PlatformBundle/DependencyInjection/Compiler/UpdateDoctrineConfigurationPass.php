<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class UpdateDoctrineConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // to fix the failure of {@see Symfony\Bridge\Doctrine\CacheWarmer\ProxyCacheWarmer} in prod mode,
        // we have to enable auto-generation of proxy classes if the platform is not installed yet,
        // because Doctrine class metadata can be loaded only if a database connection is properly configured
        $isInstalled = $container->hasParameter('installed') && $container->getParameter('installed');
        if (!$isInstalled) {
            $container->setParameter('doctrine.orm.auto_generate_proxy_classes', true);
        }
    }
}
