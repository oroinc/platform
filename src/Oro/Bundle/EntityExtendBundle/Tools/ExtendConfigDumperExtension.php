<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

abstract class ExtendConfigDumperExtension
{
    /**
     * @param string                  $actionType preUpdate or postUpdate
     * @param ConfigProvider          $extendProvider
     * @param array|ConfigInterface[] $extendConfigs
     *
     * @return mixed
     */
    abstract public function supports($actionType, ConfigProvider $extendProvider, array $extendConfigs);

    /**
     * Optionally can be overridden in child
     *
     * @param ConfigProvider $extendProvider
     * @param array          $extendConfigs
     */
    public function preUpdate(ConfigProvider $extendProvider, array &$extendConfigs)
    {
    }

    /**
     * Optionally can be overridden in child
     *
     * @param ConfigProvider $extendProvider
     * @param array          $extendConfigs
     */
    public function postUpdate(ConfigProvider $extendProvider, array &$extendConfigs)
    {
    }
}
