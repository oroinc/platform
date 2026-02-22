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
class SyncExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('check_ws', [$this, 'checkWsConnected']),
            new TwigFunction('get_ws_config', [$this, 'getWsConfig']),
            new TwigFunction('oro_sync_get_content_tags', [$this, 'generate'])
        ];
    }

    public function checkWsConnected(): bool
    {
        return $this->getConnectionChecker()->checkConnection();
    }

    public function generate(mixed $data, bool $includeCollectionTag = false, bool $processNestedData = true): array
    {
        // enforce plain array should returns
        return array_values($this->getTagGenerator()->generate($data, $includeCollectionTag, $processNestedData));
    }

    public function getWsConfig(): array
    {
        $configProvider = $this->getWsConfigurationProvider();

        return [
            'host' => $configProvider->getHost(),
            'port' => $configProvider->getPort(),
            'path' => $configProvider->getPath()
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            TagGeneratorInterface::class,
            ConnectionChecker::class,
            'oro_sync.client.frontend_websocket_parameters.provider' => WebsocketClientParametersProvider::class
        ];
    }

    private function getTagGenerator(): TagGeneratorInterface
    {
        return $this->container->get(TagGeneratorInterface::class);
    }

    private function getConnectionChecker(): ConnectionChecker
    {
        return $this->container->get(ConnectionChecker::class);
    }

    private function getWsConfigurationProvider(): WebsocketClientParametersProvider
    {
        return $this->container->get('oro_sync.client.frontend_websocket_parameters.provider');
    }
}
