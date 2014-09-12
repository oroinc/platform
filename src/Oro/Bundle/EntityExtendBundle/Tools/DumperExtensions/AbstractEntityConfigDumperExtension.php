<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

abstract class AbstractEntityConfigDumperExtension
{
    /**
     * Returns TRUE if this class supports the given action type.
     *
     * @param string $actionType Can be any of ExtendConfigDumper::ACTION_*
     *
     * @return bool
     */
    abstract public function supports($actionType);

    /**
     * Performs modifications of entity configs before entity schema is generated
     */
    public function preUpdate()
    {
    }

    /**
     * Performs modifications of entity configs after entity schema is generated
     */
    public function postUpdate()
    {
    }
}
