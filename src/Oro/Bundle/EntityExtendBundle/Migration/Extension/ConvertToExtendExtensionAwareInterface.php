<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see ConvertToExtendExtension}.
 */
interface ConvertToExtendExtensionAwareInterface
{
    public function setConvertToExtendExtension(ConvertToExtendExtension $convertToExtendExtension);
}
