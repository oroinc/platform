<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

abstract class ExtendConfigDumperExtension
{
    /**
     * @param string $actionType preUpdate or postUpdate
     *
     * @return mixed
     */
    abstract public function supports($actionType);

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
