<?php

namespace Oro\Bundle\EmailBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Oro\Component\PhpUtils\ClassLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

class OroEmailBundle extends Bundle
{
    private const ENTITY_PROXY_NAMESPACE = 'OroEntityProxy\OroEmailBundle';
    private const CACHED_ENTITIES_DIR_NAME = 'oro_entities';

    public function __construct(KernelInterface $kernel)
    {
        // register email address proxy class loader
        $loader = new ClassLoader(
            self::ENTITY_PROXY_NAMESPACE . '\\',
            $kernel->getCacheDir() . DIRECTORY_SEPARATOR . self::CACHED_ENTITIES_DIR_NAME
        );
        $loader->register();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->addDoctrineOrmMappingsPass($container);
        $container->addCompilerPass(new Compiler\LazyTransportsPass());
        $container->addCompilerPass(new Compiler\EmailOwnerConfigurationPass());
        $container->addCompilerPass(new Compiler\EmailSynchronizerPass());
        $container->addCompilerPass(new Compiler\EmailTemplateVariablesPass());
        $container->addCompilerPass(new Compiler\TwigSandboxConfigurationPass());
        $container->addCompilerPass(new Compiler\EmailRecipientsProviderPass());
        $container->addCompilerPass(new Compiler\MailboxProcessPass());
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_email.emailtemplate.variable_processor',
            'oro_email.emailtemplate.variable_processor',
            'alias'
        ));
    }

    /**
     * Add a compiler pass handles ORM mappings of email address proxy
     */
    private function addDoctrineOrmMappingsPass(ContainerBuilder $container)
    {
        $entityCacheDir = sprintf(
            '%s%s%s%s%s',
            $container->getParameter('kernel.cache_dir'),
            DIRECTORY_SEPARATOR,
            self::CACHED_ENTITIES_DIR_NAME,
            DIRECTORY_SEPARATOR,
            str_replace('\\', DIRECTORY_SEPARATOR, self::ENTITY_PROXY_NAMESPACE)
        );

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

        $container->addCompilerPass(new Compiler\EmailEntityPass(
            self::ENTITY_PROXY_NAMESPACE,
            $entityCacheDir
        ));
    }
}
