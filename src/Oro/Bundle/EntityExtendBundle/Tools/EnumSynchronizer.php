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
        /* @todo: will be implemented later
        $enumConfigProvider   = $this->configManager->getProvider('enum');
        $enumConfigs          = $enumConfigProvider->getConfigs();

        foreach ($enumConfigs as $enumEntityConfig) {
            $enumFieldConfig = $enumConfigProvider->getConfigs($enumEntityConfig->getId()->getClassName());


            $targetEntityConfig = null;
            // sync enum field settings with entity
            if ($enumFieldConfig->has('enum_code') && $enumFieldConfig->has('enum_public')) {
                $targetEntityConfig
                    ->set('code', $enumFieldConfig->get('enum_code'))
                    ->set('public', $enumFieldConfig->get('enum_public'));
            }
        }
        */
    }
}
