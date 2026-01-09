<?php

namespace Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor;

/**
 * Provides default empty implementations for entity API handler lifecycle methods.
 *
 * This base class implements the {@see EntityApiHandlerInterface} with no-op methods,
 * allowing subclasses to override only the specific lifecycle hooks they need
 * (preProcess, beforeProcess, afterProcess, invalidateProcess).
 */
abstract class AbstractEntityApiHandler implements EntityApiHandlerInterface
{
    #[\Override]
    public function preProcess($entity)
    {
    }

    #[\Override]
    public function beforeProcess($entity)
    {
    }

    #[\Override]
    public function afterProcess($entity)
    {
    }

    #[\Override]
    public function invalidateProcess($entity)
    {
    }
}
