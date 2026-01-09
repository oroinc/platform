<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

/**
 * Provides common functionality for entity configuration dumper extensions.
 *
 * This base class defines the extension point interface for modifying entity configurations
 * before and after entity schema generation. Subclasses should implement the `supports` method
 * to declare which action types they handle, and override preUpdate/postUpdate as needed.
 */
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
