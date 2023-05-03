<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Parser;

class OroAttachmentExtension extends Extension implements PrependExtensionInterface
{
    private const IMAGINE_DATA_ROOT = '%kernel.project_dir%/public';
    private const IMAGINE_FILE_MANAGER = 'oro_attachment.manager.public_mediacache';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->prependExtensionConfig($this->getAlias(), SettingsBuilder::getSettings($config));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('form.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('controllers_api.yml');
        $loader->load('mq_topics.yml');
        $loader->load('mq_processors.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }

        $container->setParameter('oro_attachment.debug_images', $config['debug_images']);
        $container->setParameter('oro_attachment.upload_file_mime_types', $config['upload_file_mime_types']);
        $container->setParameter('oro_attachment.upload_image_mime_types', $config['upload_image_mime_types']);
        $container->setParameter('oro_attachment.processors_allowed', $config['processors_allowed']);
        $container->setParameter('oro_attachment.png_quality', $config['png_quality']);
        $container->setParameter('oro_attachment.jpeg_quality', $config['jpeg_quality']);
        $container->setParameter(
            'oro_attachment.webp_strategy',
            \function_exists('imagewebp') ? $config['webp_strategy'] : WebpConfiguration::DISABLED
        );
        $container->setParameter(
            'oro_attachment.collect_attachment_files_batch_size',
            $config['cleanup']['collect_attachment_files_batch_size']
        );
        $container->setParameter(
            'oro_attachment.load_existing_attachments_batch_size',
            $config['cleanup']['load_existing_attachments_batch_size']
        );
        $container->setParameter(
            'oro_attachment.load_attachments_batch_size',
            $config['cleanup']['load_attachments_batch_size']
        );

        $yaml = new Parser();
        $value = $yaml->parse(file_get_contents(__DIR__ . '/../Resources/config/files.yml'));
        $container->setParameter('oro_attachment.files', $value['file-icons']);
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        if ($container instanceof ExtendedContainerBuilder) {
            $this->configureImagine($container);
        }
    }

    private function configureImagine(ExtendedContainerBuilder $container): void
    {
        $configs = $this->ensureImagineDefaultConfigSet($container->getExtensionConfig('liip_imagine'));
        /**
         * add empty config for "default" loader and resolver to each config item to avoid misconfiguration
         * @see \Liip\ImagineBundle\DependencyInjection\Configuration::getConfigTreeBuilder
         */
        foreach ($configs as $i => $config) {
            if (!isset($config['loaders']['default'])) {
                $configs[$i]['loaders']['default'] = [];
            }
            if (!isset($config['resolvers']['default'])) {
                $configs[$i]['resolvers']['default'] = [];
            }
        }

        $container->setExtensionConfig('liip_imagine', $configs);
    }

    private function ensureImagineDefaultConfigSet(array $configs): array
    {
        /**
         * update the last config item that contains "default" loader and resolver to avoid misconfiguration
         * due to {@see \Liip\ImagineBundle\DependencyInjection\Configuration} is not intended to work with several
         * config items contains "default" loader or resolver
         */
        /** @var int $i */
        $i = $this->getImagineDefaultConfigIndex($configs);
        if (null === $i) {
            $configs[] = [
                'loaders'   => ['default' => ['filesystem' => null]],
                'resolvers' => ['default' => ['oro_gaufrette' => null]]
            ];
            $i = \count($configs) - 1;
        }
        if ($this->isImagineDefaultLoaderFilesystem($configs)) {
            if (!$this->hasImagineDefaultLoaderDataRoot($configs[$i])) {
                $configs[$i]['loaders']['default']['filesystem']['data_root'] = self::IMAGINE_DATA_ROOT;
            }
            if (!$this->hasImagineDefaultLoaderBundleResourcesEnabled($configs[$i])) {
                $configs[$i]['loaders']['default']['filesystem']['bundle_resources']['enabled'] = true;
            }
        }
        if ($this->isImagineDefaultResolverOroGaufrette($configs)
            && !$this->hasImagineDefaultResolverFileManagerService($configs[$i])
        ) {
            $configs[$i]['resolvers']['default']['oro_gaufrette']['file_manager_service'] = self::IMAGINE_FILE_MANAGER;
        }

        return $configs;
    }

    private function getImagineDefaultConfigIndex(array $configs): ?int
    {
        $lastIndex = null;
        foreach ($configs as $i => $config) {
            if (!empty($config['loaders']) && \array_key_exists('default', $config['loaders'])) {
                $lastIndex = $i;
            } elseif (!empty($config['resolvers']) && \array_key_exists('default', $config['resolvers'])) {
                $lastIndex = $i;
            }
        }

        return $lastIndex;
    }

    private function isImagineDefaultLoaderFilesystem(array $configs): bool
    {
        foreach ($configs as $config) {
            if (!empty($config['loaders']['default'])
                && !\array_key_exists('filesystem', $config['loaders']['default'])
            ) {
                return false;
            }
        }

        return true;
    }

    private function isImagineDefaultResolverOroGaufrette(array $configs): bool
    {
        foreach ($configs as $config) {
            if (!empty($config['resolvers']['default'])
                && !\array_key_exists('oro_gaufrette', $config['resolvers']['default'])
            ) {
                return false;
            }
        }

        return true;
    }

    private function hasImagineDefaultLoaderDataRoot(array $config): bool
    {
        return
            !empty($config['loaders']['default']['filesystem'])
            && \array_key_exists('data_root', $config['loaders']['default']['filesystem']);
    }

    private function hasImagineDefaultLoaderBundleResourcesEnabled(array $config): bool
    {
        return
            !empty($config['loaders']['default']['filesystem']['bundle_resources'])
            && \array_key_exists('enabled', $config['loaders']['default']['filesystem']['bundle_resources']);
    }

    private function hasImagineDefaultResolverFileManagerService(array $config): bool
    {
        return
            !empty($config['resolvers']['default']['oro_gaufrette'])
            && \array_key_exists('file_manager_service', $config['resolvers']['default']['oro_gaufrette']);
    }
}
