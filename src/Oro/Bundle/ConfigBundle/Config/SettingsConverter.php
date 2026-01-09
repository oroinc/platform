<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Entity\Config;

/**
 * Converts configuration entities to settings array format.
 *
 * Transforms {@see Config} entity objects into a structured array representation suitable
 * for use throughout the application. The conversion organizes configuration values
 * by section and name, and includes metadata such as creation and update timestamps
 * along with scope inheritance flags.
 */
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
