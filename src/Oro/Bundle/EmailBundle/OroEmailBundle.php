<?php

namespace Oro\Bundle\EmailBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\ClassLoader\UniversalClassLoader;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailOwnerConfigurationPass;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailBodyLoaderPass;

class OroEmailBundle extends Bundle
{
    const ENTITY_PROXY_NAMESPACE   = 'OroEntityProxy\OroEmailBundle';
    const CACHED_ENTITIES_DIR_NAME = 'oro_entities';

    /**
     * Constructor
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        // register email address proxy class loader
        $loader = new UniversalClassLoader();
        $loader->registerNamespaces(
            [
                self::ENTITY_PROXY_NAMESPACE =>
                    $kernel->getCacheDir() . DIRECTORY_SEPARATOR . self::CACHED_ENTITIES_DIR_NAME
            ]
        );
        $loader->register();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EmailOwnerConfigurationPass());
        $this->addDoctrineOrmMappingsPass($container);
        $container->addCompilerPass(new EmailBodyLoaderPass());
    }

    /**
     * Add a compiler pass handles ORM mappings of email address proxy
     *
     * @param ContainerBuilder $container
     */
    protected function addDoctrineOrmMappingsPass(ContainerBuilder $container)
    {
        $entityCacheDir = sprintf(
            '%s%s%s%s%s',
            $container->getParameter('kernel.cache_dir'),
            DIRECTORY_SEPARATOR,
            self::CACHED_ENTITIES_DIR_NAME,
            DIRECTORY_SEPARATOR,
            str_replace('\\', DIRECTORY_SEPARATOR, self::ENTITY_PROXY_NAMESPACE)
        );

        $container->setParameter('oro_email.entity.cache_dir', $entityCacheDir);
        $container->setParameter('oro_email.entity.cache_namespace', self::ENTITY_PROXY_NAMESPACE);
        $container->setParameter('oro_email.entity.proxy_name_template', '%sProxy');

        // Ensure the cache directory exists
        $fs = new Filesystem();
        if (!is_dir($entityCacheDir)) {
            $fs->mkdir($entityCacheDir, 0777);
        }

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createYamlMappingDriver(
                [$entityCacheDir => self::ENTITY_PROXY_NAMESPACE]
            )
        );
    }
}
