<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

abstract class AbstractEntityConfigDumperExtension
{
    /**
     * @param string $actionType preUpdate or postUpdate
     *
     * @return mixed
     */
    abstract public function supports($actionType);

    /**
     * Entities configs for extend scope,
     * optionally can be overridden in child, can change extend config
     *
     * @param array|ConfigInterface[] $extendConfigs
     */
    public function preUpdate(array &$extendConfigs)
    {
    }

    /**
     * Entities configs for extend scope,
     * optionally can be overridden in child, can change extend config
     *
     * @param array|ConfigInterface[] $extendConfigs
     */
    public function postUpdate(array &$extendConfigs)
    {
    }
}
