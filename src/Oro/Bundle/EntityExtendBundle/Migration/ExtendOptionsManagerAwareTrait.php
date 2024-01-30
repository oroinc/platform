<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

/**
 * This trait can be used by migrations that implement {@see ExtendOptionsManagerAwareInterface}.
 */
trait ExtendOptionsManagerAwareTrait
{
    private ExtendOptionsManager $extendOptionsManager;

    public function setExtendOptionsManager(ExtendOptionsManager $extendOptionsManager): void
    {
        $this->extendOptionsManager = $extendOptionsManager;
    }
}
