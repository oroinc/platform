<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

/**
 * All virtual audit types of the application as one {@see AuditTypeInterface}, so that the audit grid, its
 * filters and its templates stay unaware of which domains are audited.
 */
class AuditTypeRegistry implements AuditTypeInterface
{
    /**
     * @param iterable<AuditTypeInterface> $auditTypes
     */
    public function __construct(
        private readonly iterable $auditTypes
    ) {
    }

    #[\Override]
    public function getTypes(): array
    {
        $types = [];
        foreach ($this->auditTypes as $auditType) {
            $types += $auditType->getTypes();
        }

        return $types;
    }

    #[\Override]
    public function getTypeLabel(string $objectClass): ?string
    {
        foreach ($this->auditTypes as $auditType) {
            $label = $auditType->getTypeLabel($objectClass);
            if (null !== $label) {
                return $label;
            }
        }

        return null;
    }

    #[\Override]
    public function getFieldLabel(string $objectClass, string $field): ?string
    {
        foreach ($this->auditTypes as $auditType) {
            $label = $auditType->getFieldLabel($objectClass, $field);
            if (null !== $label) {
                return $label;
            }
        }

        return null;
    }

    #[\Override]
    public function getMatchingFieldGroups(string $term): array
    {
        $groups = [];
        foreach ($this->auditTypes as $auditType) {
            foreach ($auditType->getMatchingFieldGroups($term) as $group) {
                $groups[] = $group;
            }
        }

        return $groups;
    }
}
