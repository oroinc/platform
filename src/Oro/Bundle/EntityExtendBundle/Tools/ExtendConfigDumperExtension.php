<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

abstract class ExtendConfigDumperExtension
{
    /**
     * @param string                  $actionType preUpdate or postUpdate
     * @param array|ConfigInterface[] $extendConfigs
     *
     * @return mixed
     */
    abstract public function supports($actionType, array $extendConfigs);

    /**
     * Optionally can be overridden in child
     *
     * @param array          $extendConfigs
     */
    public function preUpdate(array &$extendConfigs)
    {
    }

    /**
     * Optionally can be overridden in child
     *
     * @param array          $extendConfigs
     */
    public function postUpdate(array &$extendConfigs)
    {
    }
}
