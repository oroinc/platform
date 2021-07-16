<?php

namespace Oro\Bundle\EmailBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Oro\Component\PhpUtils\ClassLoader;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The EmailBundle bundle class.
 */
class OroEmailBundle extends Bundle
{
    private const ENTITY_PROXY_NAMESPACE   = 'OroEntityProxy\OroEmailBundle';
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
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $this->addDoctrineOrmMappingsPass($container);
        $container->addCompilerPass(new Compiler\EmailOwnerConfigurationPass());
        $container->addCompilerPass(new Compiler\EmailSynchronizerPass());
        $container->addCompilerPass(new Compiler\EmailTemplateVariablesPass());
        $container->addCompilerPass(new Compiler\TwigSandboxConfigurationPass());
        $container->addCompilerPass(new Compiler\EmailRecipientsProviderPass());
        $container->addCompilerPass(new Compiler\MailboxProcessPass());
        $container->addCompilerPass(new Compiler\OverrideServiceSwiftMailer());
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_email.emailtemplate.variable_processor',
            'oro_email.emailtemplate.variable_processor',
            'alias'
        ));
        $container->addCompilerPass(new Compiler\SwiftMailerTransportPass(), PassConfig::TYPE_OPTIMIZE);

        $addTopicPass = AddTopicMetaPass::create()
            ->add(Topics::SEND_AUTO_RESPONSE, 'Send auto response for single email')
            ->add(Topics::SEND_AUTO_RESPONSES, 'Send auto response for multiple emails')
            ->add(Topics::UPDATE_ASSOCIATIONS_TO_EMAILS, 'Update associations to emails')
            ->add(Topics::ADD_ASSOCIATION_TO_EMAIL, 'Add association to single email')
            ->add(Topics::ADD_ASSOCIATION_TO_EMAILS, 'Add association to multiple emails')
            ->add(Topics::UPDATE_EMAIL_OWNER_ASSOCIATION, 'Updates single email for email owner')
            ->add(Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS, 'Updates multiple emails for email owner')
            ->add(Topics::SYNC_EMAIL_SEEN_FLAG, 'Synchronization email flags')
            ->add(Topics::PURGE_EMAIL_ATTACHMENTS, 'Purge email attachments')
            ->add(Topics::PURGE_EMAIL_ATTACHMENTS_BY_IDS, 'Purge email attachments by ids');

        $container->addCompilerPass($addTopicPass);
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
