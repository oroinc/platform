<?php

namespace Oro\Bundle\ConfigBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction('oro_config_value', [$this, 'getConfigValue'])
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'config_extension';
    }
}
