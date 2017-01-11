<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Entity\Config;

class SettingsConverter
{
    /**
     * @param Config $config
     *
     * @return array
     */
    public static function convertToSettings(Config $config)
    {
        $settings = [];
        foreach ($config->getValues() as $value) {
            $settings[$value->getSection()][$value->getName()] = [
                'value' => $value->getValue(),
                'use_parent_scope_value' => false,
                'createdAt' => $value->getCreatedAt(),
                'updatedAt' => $value->getUpdatedAt()
            ];
        }

        return $settings;
    }
}
