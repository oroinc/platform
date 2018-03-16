<?php

namespace Oro\Bundle\EmailBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailBodyLoaderPass;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailFlagManagerLoaderPass;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailOwnerConfigurationPass;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailRecipientsProviderPass;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailSynchronizerPass;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\EmailTemplateVariablesPass;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\MailboxProcessPass;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\OverrideServiceSwiftMailer;
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Component\DependencyInjection\Compiler\TaggedServiceLinkRegistryCompilerPass;
use Oro\Component\PhpUtils\ClassLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

class OroEmailBundle extends Bundle
{
    const ENTITY_PROXY_NAMESPACE   = 'OroEntityProxy\OroEmailBundle';
    const CACHED_ENTITIES_DIR_NAME = 'oro_entities';
    const VARIABLE_PROCESSOR_TAG = 'oro_email.emailtemplate.variable_processor';

    /**
     * Constructor
     *
     * @param KernelInterface $kernel
     */
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

        $container->addCompilerPass(new EmailOwnerConfigurationPass());
        $this->addDoctrineOrmMappingsPass($container);
        $container->addCompilerPass(new EmailBodyLoaderPass());
        $container->addCompilerPass(new EmailFlagManagerLoaderPass());
        $container->addCompilerPass(new EmailSynchronizerPass());
        $container->addCompilerPass(new EmailTemplateVariablesPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new EmailRecipientsProviderPass());
        $container->addCompilerPass(new MailboxProcessPass());
        $container->addCompilerPass(new OverrideServiceSwiftMailer());

        $container->addCompilerPass(
            new TaggedServiceLinkRegistryCompilerPass(
                self::VARIABLE_PROCESSOR_TAG,
                'oro_email.emailtemplate.variable_processor'
            )
        );

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
