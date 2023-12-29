<?php

namespace Oro\Bundle\EntityBundle\Migrations\Extension;

/**
 * This trait can be used by migrations that implement {@see ChangeTypeExtensionAwareInterface}.
 */
trait ChangeTypeExtensionAwareTrait
{
    private ChangeTypeExtension $changeTypeExtension;

    public function setChangeTypeExtension(ChangeTypeExtension $changeTypeExtension): void
    {
        $this->changeTypeExtension = $changeTypeExtension;
    }
}
