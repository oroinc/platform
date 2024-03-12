<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see ConvertToExtendExtensionAwareInterface}.
 */
trait ConvertToExtendExtensionAwareTrait
{
    private ConvertToExtendExtension $convertToExtendExtension;

    public function setConvertToExtendExtension(ConvertToExtendExtension $convertToExtendExtension): void
    {
        $this->convertToExtendExtension = $convertToExtendExtension;
    }
}
