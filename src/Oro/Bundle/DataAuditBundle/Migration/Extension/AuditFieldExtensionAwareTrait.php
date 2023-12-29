<?php

namespace Oro\Bundle\DataAuditBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see AuditFieldExtensionAwareInterface}.
 */
trait AuditFieldExtensionAwareTrait
{
    private AuditFieldExtension $auditFieldExtension;

    public function setAuditFieldExtension(AuditFieldExtension $extension): void
    {
        $this->auditFieldExtension = $extension;
    }
}
