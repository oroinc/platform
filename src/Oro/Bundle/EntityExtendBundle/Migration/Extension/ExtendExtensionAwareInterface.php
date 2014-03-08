<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

/**
 * ExtendExtensionAwareInterface should be implemented by migrations that depends on a ExtendExtension.
 */
interface ExtendExtensionAwareInterface
{
    /**
     * Sets the ExtendExtension
     *
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension);
}
