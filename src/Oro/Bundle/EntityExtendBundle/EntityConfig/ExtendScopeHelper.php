<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

class ExtendScopeHelper
{
    /**
     * @param ConfigInterface $extendConfig
     *
     * @return bool
     */
    public static function isAvailableForProcessing(ConfigInterface $extendConfig)
    {
        $extendConfigValues = $extendConfig->getValues();

        $extendConfigState = '';
        if (isset($extendConfigValues['state'])) {
            $extendConfigState = $extendConfigValues['state'];
        }

        return static::isStateAvailableForProcessing($extendConfigState);
    }

    /**
     * @param $state
     *
     * @return bool
     */
    public static function isStateAvailableForProcessing($state)
    {
        $unsupportedState = [
            ExtendScope::STATE_NEW,
            ExtendScope::STATE_DELETE
        ];

        if (is_array($state) && isset($state['state'])) {
            $state = $state['state'];
        }

        return !in_array($state, $unsupportedState, true);
    }
}
