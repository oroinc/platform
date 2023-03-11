<?php

namespace Oro\Bundle\GaufretteBundle\DependencyInjection;

use Oro\Bundle\GaufretteBundle\DependencyInjection\Factory\ConfigurationFactoryInterface;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class OroGaufretteExtension extends Extension implements PrependExtensionInterface
{
    private const ADAPTER_PARAM_PREFIX    = 'gaufrette_adapter.';
    private const FILESYSTEM_PARAM_PREFIX = 'gaufrette_filesystem.';

    /** @var ConfigurationFactoryInterface[] */
    private $configurationFactories = [];

    public function addConfigurationFactory(ConfigurationFactoryInterface $configurationFactory)
    {
        $this->configurationFactories[$configurationFactory->getKey()] = $configurationFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('controllers.yml');
        $loader->load('commands.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }

        $this->configureReadonlyProtocol($container, $config['stream_wrapper']['readonly_protocol']);
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        if ($container instanceof ExtendedContainerBuilder) {
            $this->addGaufretteConfig($container, $this->configureAdapters($container));
            $this->addGaufretteConfig($container, $this->configureFilesystems($container));
        }

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->setParameter(
                'oro_gaufrette.tests.test_local_adapter_directory',
                rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'oro_test_local_fs'
            );
            $fileLocator = new FileLocator(__DIR__ . '/../Tests/Functional/Environment');
            $configData = Yaml::parse(file_get_contents($fileLocator->locate('app.yml')));
            foreach ($configData as $name => $config) {
                $container->prependExtensionConfig($name, $config);
            }
        }
    }

    private function configureReadonlyProtocol(ContainerBuilder $container, ?string $readonlyProtocol): void
    {
        if (!$container->hasParameter('knp_gaufrette.stream_wrapper.protocol')) {
            return;
        }

        if (!$readonlyProtocol) {
            $protocol = $container->getParameter('knp_gaufrette.stream_wrapper.protocol');
            if ($protocol) {
                $readonlyProtocol = $protocol . '-readonly';
            }
        }
        $container->setParameter('oro_gaufrette.stream_wrapper.readonly_protocol', $readonlyProtocol ?? '');
    }

    private function configureAdapters(ContainerBuilder $container): array
    {
        $configs = [];
        $adapterNames = $this->getAdapterNames($container);
        $parameters = $container->getParameterBag()->all();
        foreach ($parameters as $paramName => $value) {
            if (!str_starts_with($paramName, self::ADAPTER_PARAM_PREFIX)) {
                continue;
            }
            $adapterName = substr($paramName, \strlen(self::ADAPTER_PARAM_PREFIX));
            if (!\in_array($adapterName, $adapterNames, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'The "%s" parameter name is invalid because the "%s" Gaufrette adapter does not exist.'
                    . ' Known adapters: %s.',
                    $paramName,
                    $adapterName,
                    implode(', ', $adapterNames)
                ));
            }
            if (!$value) {
                continue;
            }
            $configs[] = $this->configureAdapter($adapterName, $value);
        }
        if ($configs) {
            $configs = array_merge_recursive(...$configs);
        }

        return $configs;
    }

    private function configureFilesystems(ContainerBuilder $container): array
    {
        $configs = [];
        $filesystemNames = $this->getFilesystemNames($container);
        $parameters = $container->getParameterBag()->all();
        foreach ($parameters as $paramName => $value) {
            if (!str_starts_with($paramName, self::FILESYSTEM_PARAM_PREFIX)) {
                continue;
            }
            $filesystemName = substr($paramName, \strlen(self::FILESYSTEM_PARAM_PREFIX));
            if (!\in_array($filesystemName, $filesystemNames, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'The "%s" parameter name is invalid because the "%s" Gaufrette filesystem does not exist.'
                    . ' Known filesystems: %s.',
                    $paramName,
                    $filesystemName,
                    implode(', ', $filesystemNames)
                ));
            }
            if (!$value) {
                continue;
            }
            $config = $this->configureAdapter($filesystemName, $value);
            $config['filesystems'][$filesystemName] = [
                'adapter' => $filesystemName,
                'alias'   => $filesystemName . '_filesystem'
            ];
            $configs[] = $config;
        }
        if ($configs) {
            $configs = array_merge_recursive(...$configs);
        }

        return $configs;
    }

    private function configureAdapter(string $adapterName, string $configString): array
    {
        foreach ($this->configurationFactories as $key => $configurationFactory) {
            if (!str_starts_with($configString, $key . ':')) {
                continue;
            }

            return [
                'adapters' => [
                    $adapterName => $configurationFactory->getAdapterConfiguration(
                        substr($configString, \strlen($key) + 1)
                    )
                ]
            ];
        }
        $delimiterPos = strpos($configString, ':');
        if (false === $delimiterPos) {
            throw new \InvalidArgumentException(sprintf(
                'The Gaufrette configuration string "%s" is invalid because it must start with an adapter type'
                . ' separated by colon (:). %s',
                $configString,
                $this->getAdapterTypeHints()
            ));
        }
        throw new \InvalidArgumentException(sprintf(
            'The Gaufrette configuration string "%s" is invalid because the adapter type "%s" does not exist. %s',
            $configString,
            substr($configString, 0, $delimiterPos),
            $this->getAdapterTypeHints()
        ));
    }

    private function getAdapterTypeHints(): string
    {
        $hints = [];
        foreach ($this->configurationFactories as $key => $configurationFactory) {
            $hints[] = sprintf('  %s - %s', $key, $configurationFactory->getHint());
        }

        return sprintf(
            "Known adapter types: %s.\nHints:\n%s",
            implode(', ', array_keys($this->configurationFactories)),
            implode("\n", $hints)
        );
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function getAdapterNames(ContainerBuilder $container): array
    {
        return $this->getGaufretteItemNames($container, 'adapters');
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function getFilesystemNames(ContainerBuilder $container): array
    {
        return $this->getGaufretteItemNames($container, 'filesystems');
    }

    private function getGaufretteItemNames(ContainerBuilder $container, string $key): array
    {
        $result = [];
        $configs = $this->getGaufretteConfigs($container);
        foreach ($configs as $config) {
            if (!empty($config[$key])) {
                $result[] = array_keys($config[$key]);
            }
        }
        if ($result) {
            $result = array_unique(array_merge(...$result));
        }

        return $result;
    }

    private function getGaufretteConfigs(ContainerBuilder $container): array
    {
        return $container->getExtensionConfig('knp_gaufrette');
    }

    private function addGaufretteConfig(ExtendedContainerBuilder $container, array $config): void
    {
        if ($config) {
            $configs = $this->getGaufretteConfigs($container);
            $configs[] = $config;
            $container->setExtensionConfig('knp_gaufrette', $configs);
        }
    }
}
