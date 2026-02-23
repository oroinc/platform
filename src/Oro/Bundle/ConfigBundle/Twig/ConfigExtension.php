<?php

namespace Oro\Bundle\ConfigBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve values of configuration settings:
 *   - oro_config_value
 */
class ConfigExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_config_value', [$this, 'getConfigValue']),
        ];
    }

    public function getConfigValue(string $name): mixed
    {
        return $this->getConfigManager()->get($name);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ConfigManager::class
        ];
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->container->get(ConfigManager::class);
    }
}
