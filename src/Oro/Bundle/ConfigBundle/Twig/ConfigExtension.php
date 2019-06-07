<?php

namespace Oro\Bundle\ConfigBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to retrieve values of configuration settings and container parameters:
 *   - oro_config_value
 *   - oro_parameter
 */
class ConfigExtension extends AbstractExtension
{
    /** @var ContainerInterface */
    protected $container;

    /** @var ConfigManager|null */
    private $configManager;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        if (null === $this->configManager) {
            $this->configManager = $this->container->get('oro_config.user');
        }

        return $this->configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_config_value', [$this, 'getConfigValue']),
            new TwigFunction('oro_parameter', [$this, 'getParameter']),
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

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->container->getParameter($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'config_extension';
    }
}
