<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

/**
 * Defines the contract for entities that provide additional fields to be included in audit logs.
 *
 * Entities implementing this interface can specify custom data to be tracked in the audit trail
 * beyond the standard field changes. This allows for flexible audit logging of computed values,
 * related entity information, or any other contextual data relevant to the audit history.
 */
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
