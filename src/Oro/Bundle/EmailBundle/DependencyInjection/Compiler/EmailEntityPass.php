<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Defines container parameters that responsible for email entity proxy and email entity cache.
 */
class EmailEntityPass implements CompilerPassInterface
{
    /** @var string */
    private $entityProxyNamespace;

    /** @var string */
    private $entityCacheDir;

    /**
     * @param string $entityProxyNamespace
     * @param string $entityCacheDir
     */
    public function __construct($entityProxyNamespace, $entityCacheDir)
    {
        $this->entityProxyNamespace = $entityProxyNamespace;
        $this->entityCacheDir = $entityCacheDir;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->setParameter('oro_email.entity.cache_dir', $this->entityCacheDir);
        $container->setParameter('oro_email.entity.cache_namespace', $this->entityProxyNamespace);
        $container->setParameter('oro_email.entity.proxy_name_template', '%sProxy');
    }
}
