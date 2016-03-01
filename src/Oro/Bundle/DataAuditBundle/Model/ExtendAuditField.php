<?php

namespace Oro\Bundle\DataAuditBundle\Model;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;

class ExtendAuditField extends AbstractAuditField
{
    /**
     * Constructor
     *
     * The real implementation of this method is auto generated.
     *
     * IMPORTANT: If the derived class has own constructor it must call parent constructor.
     *
     * @param AbstractAudit $audit
     * @param string $field
     * @param string $dataType
     * @param mixed $newValue
     * @param mixed $oldValue
     */
    public function __construct(AbstractAudit $audit, $field, $dataType, $newValue, $oldValue)
    {
        parent::__construct($audit, $field, $dataType, $newValue, $oldValue);
    }
}
