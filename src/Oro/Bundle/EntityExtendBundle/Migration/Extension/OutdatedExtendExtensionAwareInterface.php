<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see OutdatedExtendExtension}.
 */
interface OutdatedExtendExtensionAwareInterface
{
    public function setOutdatedExtendExtension(OutdatedExtendExtension $extendExtension);
}
