<?php

namespace Oro\Bundle\EntityBundle\Migrations\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see ChangeTypeExtension}.
 */
interface ChangeTypeExtensionAwareInterface
{
    public function setChangeTypeExtension(ChangeTypeExtension $changeTypeExtension);
}
