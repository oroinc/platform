<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class ConfigContextConfigurator implements ContextConfiguratorInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var string[] */
    protected $map;

    /**
     * @param ConfigManager $configManager
     * @param string[]      $configKeyMap
     */
    public function __construct(ConfigManager $configManager, array $configKeyMap = [])
    {
        $this->configManager = $configManager;
        $this->map           = $configKeyMap;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $normalizers = [];
        foreach ($this->map as $alias => $configKey) {
            $normalizers[$alias] = function (Options $options, $value) use ($configKey) {
                if ($value === null) {
                    $value = $this->configManager->get($configKey);
                }

                return $value;
            };
        }

        $context->getDataResolver()
            ->setOptional(array_keys($this->map))
            ->setNormalizers($normalizers);
    }

    /**
     * @param string $alias
     * @param string $configKey
     *
     * @throws \RuntimeException if an alias already associated with another config key
     */
    public function addConfigVariable($alias, $configKey)
    {
        if (isset($this->map[$alias])) {
            throw new \RuntimeException(
                sprintf(
                    'The alias "%s" cannot be used for variable "%s" '
                    . 'because it is already associated with variable "%s".',
                    $alias,
                    $configKey,
                    $this->map[$alias]
                )
            );
        }

        $this->map[$alias] = $configKey;
    }

    /**
     * @param string[] $configKeyMap
     *
     * @throws \RuntimeException if an alias already associated with another config key
     */
    public function addConfigVariables(array $configKeyMap)
    {
        foreach ($configKeyMap as $alias => $configKey) {
            $this->addConfigVariable($alias, $configKey);
        }
    }
}
