<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * EnumSynchronizer used to sync entity config with enum entities options
 *
 * @package Oro\Bundle\EntityExtendBundle\Tools
 */
class EnumSynchronizer
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityManager */
    protected $em;

    public function __construct(ConfigManager $configManager, EntityManager $em)
    {
        $this->configManager = $configManager;
        $this->em            = $em;
    }

    public function sync()
    {
        $enumConfigProvider   = $this->configManager->getProvider('enum');
        $enumConfigs          = $enumConfigProvider->getConfigs();

        foreach ($enumConfigs as $enumEntityConfig) {
            /** @var ConfigInterface $enumFieldConfig */
            $enumFieldConfig = $enumConfigProvider->getConfigs($enumEntityConfig->getId()->getClassName());


            /** @var ConfigInterface $enumFieldConfig   enum value entity config */
            $targetEntityConfig = null;
            // sync enum field settings with entity
            if ($enumFieldConfig->has('enum_code') && $enumFieldConfig->has('is_public')) {
                $targetEntityConfig
                    ->set('code', $enumFieldConfig->get('enum_code'))
                    ->set('public', $enumFieldConfig->get('is_public'));
            }
        }
    }
}
