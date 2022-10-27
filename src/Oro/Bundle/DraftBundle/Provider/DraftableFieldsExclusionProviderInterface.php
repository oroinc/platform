<?php

namespace Oro\Bundle\DraftBundle\Provider;

/**
 * The interface for classes that can provide a list of draftable field names.
 */
interface DraftableFieldsExclusionProviderInterface
{
    /**
     * Check if a provider supports given entity class name
     */
    public function isSupport(string $className): bool;

    /**
     * Gets a list of field names that should be excluded from the confirmation message
     *
     * @return string[]
     */
    public function getExcludedFields(): array;
}
