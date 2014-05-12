<?php

namespace Oro\Bundle\EntityConfigBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class ConfigExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_config';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_entity_config', array($this, 'getClassConfig')),
        );
    }

    /**
     * @param string $className
     * @param string $scope
     * @return array
     */
    public function getClassConfig($className, $scope = 'entity')
    {
        if (!$this->configManager->hasConfig($className)) {
            return array();
        }

        $entityConfig = new EntityConfigId($scope, $className);

        return $this->configManager->getConfig($entityConfig)->all();
    }
}
