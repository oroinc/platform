<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener;

/**
 * The listener that prohibits modifying or adding entities within Initializer.
 */
class RestrictFlushInitializerListener
{
    public function preFlush()
    {
        throw new \RuntimeException('It is prohibited to modify or add new entities within Initializer');
    }
}
