<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

/**
 * A kind of audit record whose object class is not an entity of the application but a virtual type: a
 * system configuration level, a menu level, ... Registered with the "oro_dataaudit.audit_type" tag, it
 * provides everything the audit grid shows for such a record, so that a bundle can add an audited domain
 * without changing the Data Audit bundle. A label is asked for classes {@see getTypes()} no longer
 * contains as well — a type whose package has been removed since — and null means "not of this kind".
 *
 * @see \Oro\Bundle\DataAuditBundle\Model\AuditEntry
 */
interface AuditTypeInterface
{
    /**
     * @return array<string, string> [object class => translated label] of every type of this kind
     */
    public function getTypes(): array;

    public function getTypeLabel(string $objectClass): ?string;

    public function getFieldLabel(string $objectClass, string $field): ?string;

    /**
     * @return array<array{classes: string[], fields: string[]}>
     */
    public function getMatchingFieldGroups(string $term): array;
}
