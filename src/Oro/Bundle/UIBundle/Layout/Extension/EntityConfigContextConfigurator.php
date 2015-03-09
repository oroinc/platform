<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class EntityConfigContextConfigurator implements ContextConfiguratorInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var string[] */
    protected $map;

    /**
     * @param ConfigManager $configManager
     * @param array         $configKeyMap
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
        $defaults = [];
        foreach ($this->map as $alias => $configKey) {
            $defaults[$alias] = function (Options $options, $value) use ($configKey) {
                if (null === $value) {
                    $entityClass = $options->get('entity_class');
                    if ($entityClass) {
                        list($scope, $code) = $configKey;
                        $configProvider = $this->configManager->getProvider($scope);
                        if ($configProvider && $configProvider->hasConfig($entityClass)) {
                            $value = $configProvider->getConfig($entityClass)->get($code);
                        }
                    }
                }

                return $value;
            };
        }

        $context->getResolver()->setDefaults($defaults);
    }

    /**
     * @param string $alias
     * @param array  $configKey [scope, code]
     *
     * @throws \RuntimeException if an alias already associated with another config key
     */
    public function addConfigVariable($alias, array $configKey)
    {
        if (isset($this->map[$alias])) {
            throw new \RuntimeException(
                sprintf(
                    'The alias "%s" cannot be used for variable "%s.%s" '
                    . 'because it is already associated with variable "%s.%s".',
                    $alias,
                    $configKey[0],
                    $configKey[1],
                    $this->map[$alias][0],
                    $this->map[$alias][1]
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
