<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

interface AuditAdditionalFieldsInterface
{
    /**
     * Returns array of fields that should be added to audit log.
     * There are no requirements to type of a data. If object is passed to an array,
     * it will be properly sanitized and converted to supported format.
     *
     * @return array|null
     */
    public function getAdditionalFields();
}
