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
    private ContainerInterface $container;
    private ?ConfigManager $configManager = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_config_value', [$this, 'getConfigValue']),
        ];
    }

    /**
     * @param  string $name Setting name in "{bundle}.{setting}" format
     *
     * @return mixed
     */
    public function getConfigValue($name)
    {
        return $this->getConfigManager()->get($name);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_config.manager' => ConfigManager::class
        ];
    }

    private function getConfigManager(): ConfigManager
    {
        if (!$this->configManager) {
            $this->configManager = $this->container->get('oro_config.manager');
        }

        return $this->configManager;
    }
}
