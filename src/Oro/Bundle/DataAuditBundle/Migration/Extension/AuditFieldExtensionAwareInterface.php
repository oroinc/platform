<?php

namespace Oro\Bundle\DataAuditBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see AuditFieldExtension}.
 */
interface AuditFieldExtensionAwareInterface
{
    public function setAuditFieldExtension(AuditFieldExtension $extension);
}
