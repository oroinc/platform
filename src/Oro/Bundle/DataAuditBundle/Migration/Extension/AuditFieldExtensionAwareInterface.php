<?php

namespace Oro\Bundle\DataAuditBundle\Migration\Extension;

interface AuditFieldExtensionAwareInterface
{
    public function setAuditFieldExtension(AuditFieldExtension $extension);
}
