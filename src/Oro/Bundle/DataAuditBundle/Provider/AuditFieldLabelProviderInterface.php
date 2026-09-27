<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

/**
 * Names a changed field of an audited domain the way the audit grid shows it, and matches such names
 * against the term of the "Data" filter, so that the grid and the search never name a field differently.
 * Null means the object class is not of this domain, so callers fall back to their own rendering.
 */
interface AuditFieldLabelProviderInterface
{
    public function getLabel(?string $objectClass, string $field): ?string;

    /**
     * @return array{classes: string[], fields: string[]}
     */
    public function getMatchingFields(string $term): array;
}
