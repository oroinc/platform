<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see ExtendExtension}.
 */
interface ExtendExtensionAwareInterface
{
    public function setExtendExtension(ExtendExtension $extendExtension);
}
