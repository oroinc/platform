<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection;

use Monolog\Logger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroSyncExtension extends Extension
{
    const CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST  = 'websocket_host';
    const CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT  = 'websocket_port';
    const CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH  = 'websocket_path';
    const CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS  = 'websocket_bind_address';
    const CONFIG_PARAM_WEBSOCKET_BIND_PORT     = 'websocket_bind_port';
    const CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  = 'websocket_backend_host';
    const CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  = 'websocket_backend_port';
    const CONFIG_PARAM_WEBSOCKET_BACKEND_PATH  = 'websocket_backend_path';
    const CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST = 'websocket_frontend_host';
    const CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT = 'websocket_frontend_port';
    const CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH = 'websocket_frontend_path';
    const CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT = 'websocket_backend_transport';
    const CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS = 'websocket_backend_ssl_context_options';
    const KNOWN_CONTEXT_OPTIONS = [
        'peer_name' => 'string',
        'verify_peer' => 'boolean',
        'verify_peer_name' => 'boolean',
        'allow_self_signed' => 'boolean',
        'cafile' => 'string',
        'capath' => 'string',
        'local_cert' => 'string',
        'local_pk' => 'string',
        'passphrase' => 'string',
        'CN_match' => 'string',
        'verify_depth' => 'integer',
        'ciphers' => 'string',
        'capture_peer_cert' => 'boolean',
        'capture_peer_cert_chain' => 'boolean',
        'SNI_enabled' => 'boolean',
        'SNI_server_name' => 'string',
        'disable_compression' => 'boolean',
        'peer_fingerprint' => 'array',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->setDefaultParameterValue($container, self::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT, 'tcp');
        $this->validateWebSocketBackendTransport($container);

        $this->setDefaultParameterValue($container, self::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS, []);
        $this->validateWebSocketContextOptions($container);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.yml');
        $loader->load('security.yml');
        $loader->load('client.yml');
        $loader->load('data_update.yml');

        $this->cloneParameters(
            $container,
            self::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST,
            [
                self::CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS,
                self::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST,
                self::CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST
            ]
        );
        $this->cloneParameters(
            $container,
            self::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT,
            [
                self::CONFIG_PARAM_WEBSOCKET_BIND_PORT,
                self::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT,
                self::CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT
            ]
        );

        $this->cloneParameters(
            $container,
            self::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH,
            [
                self::CONFIG_PARAM_WEBSOCKET_BACKEND_PATH,
                self::CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH
            ]
        );

        if (isset($bundles['MonologBundle'])) {
            $this->configureLogger($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $source
     * @param array $targets
     */
    private function cloneParameters(ContainerBuilder $container, $source, $targets): void
    {
        if ($container->hasParameter($source)) {
            $value = $container->getParameter($source);
            foreach ($targets as $target) {
                if (!$container->hasParameter($target)) {
                    $container->setParameter($target, $value);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureLogger(ContainerBuilder $container): void
    {
        if (true === $container->getParameter('kernel.debug')) {
            $verbosityLevels = [
                'VERBOSITY_NORMAL' => Logger::INFO,
                'VERBOSITY_VERBOSE' => Logger::DEBUG,
            ];
        } else {
            $verbosityLevels = [
                'VERBOSITY_NORMAL' => Logger::WARNING,
                'VERBOSITY_VERBOSE' => Logger::NOTICE,
                'VERBOSITY_VERY_VERBOSE' => Logger::INFO,
                'VERBOSITY_DEBUG' => Logger::DEBUG,
            ];
        }

        $monologConfig = [
            'channels' => ['oro_websocket'],
            'handlers' => [
                'websocket' => [
                    'type' => 'console',
                    'verbosity_levels' => $verbosityLevels,
                    'channels' => [
                        'type' => 'inclusive',
                        'elements' => ['oro_websocket'],
                    ],
                ],
            ],
        ];

        $container->prependExtensionConfig('monolog', $monologConfig);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $name
     * @param mixed $default
     */
    private function setDefaultParameterValue(ContainerBuilder $container, string $name, $default): void
    {
        if (!$container->hasParameter($name)) {
            $container->setParameter($name, $default);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function validateWebSocketBackendTransport(ContainerBuilder $container): void
    {
        if (!\in_array(
            $container->getParameter(self::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT),
            stream_get_transports(),
            true
        )) {
            throw new InvalidConfigurationException(sprintf(
                'Transport "%s" is not available, please run stream_get_transports() to verify'
                . ' the list of registered transports.',
                $container->getParameter(self::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT)
            ));
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function validateWebSocketContextOptions(ContainerBuilder $container): void
    {
        $options = $container->getParameter(self::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS);

        foreach ($options as $optionName => $optionValue) {
            if (!array_key_exists($optionName, self::KNOWN_CONTEXT_OPTIONS)) {
                throw new InvalidConfigurationException(sprintf(
                    'Unknown socket context option "%s". Only SSL context options '
                    . '(http://php.net/manual/en/context.ssl.php) are allowed.',
                    $optionName
                ));
            }

            if (gettype($optionValue) !== self::KNOWN_CONTEXT_OPTIONS[$optionName]) {
                throw new InvalidConfigurationException(sprintf(
                    'Invalid type "%s" of socket context option "%s", expected "%s" type.',
                    gettype($optionValue),
                    $optionName,
                    self::KNOWN_CONTEXT_OPTIONS[$optionName]
                ));
            }
        }
    }
}
