<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

/**
 * ConvertToExtendExtensionAwareInterface should be implemented by migrations
 * that depends on a ConvertToExtendExtension.
 */
interface ConvertToExtendExtensionAwareInterface
{
    /**
     * Sets the ExtendExtension
     */
    public function setConvertToExtendExtension(ConvertToExtendExtension $convertToExtendExtension);
}
