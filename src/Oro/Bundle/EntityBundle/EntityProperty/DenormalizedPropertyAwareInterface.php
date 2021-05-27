<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

/**
 * Interface to update denormalized fields from existing fields.
 * Cases:
 *   - ORM\PreUpdate and ORM\PrePersist not triggering events without entity changes and relations ignored in UoW.
 *   - Cascade options are not available or configured.
 */
interface DenormalizedPropertyAwareInterface
{
    public function updateDenormalizedProperties(): void;
}
