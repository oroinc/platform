<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see OutdatedExtendExtensionAwareInterface}.
 */
trait OutdatedExtendExtensionAwareTrait
{
    private OutdatedExtendExtension $outdatedExtendExtension;

    public function setOutdatedExtendExtension(OutdatedExtendExtension $extendExtension): void
    {
        $this->outdatedExtendExtension = $extendExtension;
    }
}
