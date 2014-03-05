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
     * @param ExtendExtension $extend
     */
    public function setExtendExtension(ExtendExtension $extend);
}
