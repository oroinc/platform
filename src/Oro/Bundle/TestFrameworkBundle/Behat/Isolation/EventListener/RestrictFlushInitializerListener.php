<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;

class RestrictFlushInitializerListener
{
    public function preFlush()
    {
        throw new RuntimeException('It is prohibited to modify or add new entities within Initializer');
    }
}
