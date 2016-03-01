<?php

namespace Oro\Bundle\DataAuditBundle\Migration\Extension;

interface AuditFieldExtensionAwareInterface
{
    /**
     * @param AuditFieldExtension $extension
     */
    public function setAuditFieldExtension(AuditFieldExtension $extension);
}
