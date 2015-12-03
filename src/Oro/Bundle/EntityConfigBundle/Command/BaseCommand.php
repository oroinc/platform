<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class BaseCommand extends ContainerAwareCommand
{
    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->getContainer()->get('oro_entity_config.config_manager');
    }
}
