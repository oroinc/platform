<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see ExtendExtensionAwareInterface}.
 */
trait ExtendExtensionAwareTrait
{
    private ExtendExtension $extendExtension;

    public function setExtendExtension(ExtendExtension $extendExtension): void
    {
        $this->extendExtension = $extendExtension;
    }
}
