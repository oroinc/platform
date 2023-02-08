<?php

namespace Oro\Bundle\SyncBundle\Twig;

use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to interact with the websocket server:
 *   - check_ws
 *   - oro_sync_get_content_tags
 */
class OroSyncExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TagGeneratorInterface
     */
    protected function getTagGenerator()
    {
        return $this->container->get('oro_sync.content.tag_generator');
    }

    /**
     * @return ConnectionChecker
     */
    protected function getConnectionChecker()
    {
        return $this->container->get('oro_sync.client.connection_checker');
    }

    /**
     * @return WebsocketClientParametersProvider
     */
    protected function getWsConfigurationProvider()
    {
        return $this->container->get('oro_sync.client.frontend_websocket_parameters.provider');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('check_ws', [$this, 'checkWsConnected']),
            new TwigFunction('get_ws_config', [$this, 'getWsConfig']),
            new TwigFunction('oro_sync_get_content_tags', [$this, 'generate'])
        ];
    }

    /**
     * Check WebSocket server connection
     *
     * @return bool True on success, false otherwise
     */
    public function checkWsConnected()
    {
        return $this->getConnectionChecker()->checkConnection();
    }

    /**
     * @param mixed $data
     * @param bool  $includeCollectionTag
     * @param bool  $processNestedData
     *
     * @return array
     */
    public function generate($data, $includeCollectionTag = false, $processNestedData = true)
    {
        // enforce plain array should returns
        return array_values($this->getTagGenerator()->generate($data, $includeCollectionTag, $processNestedData));
    }

    /**
     * @return array
     */
    public function getWsConfig()
    {
        $configProvider = $this->getWsConfigurationProvider();

        return [
            'host' => $configProvider->getHost(),
            'port' => $configProvider->getPort(),
            'path' => $configProvider->getPath()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_sync.content.tag_generator' => TagGeneratorInterface::class,
            'oro_sync.client.connection_checker' => ConnectionChecker::class,
            'oro_sync.client.frontend_websocket_parameters.provider' => WebsocketClientParametersProvider::class
        ];
    }
}
