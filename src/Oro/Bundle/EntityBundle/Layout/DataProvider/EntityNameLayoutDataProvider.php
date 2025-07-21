<?php

namespace Oro\Bundle\EntityBundle\Layout\DataProvider;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

/**
 * Provides entity name on frontend layouts
 */
class EntityNameLayoutDataProvider
{
    public function __construct(
        private readonly EntityNameResolver $entityNameResolver
    ) {
    }

    public function getName($entity, $format = null, $locale = null): ?string
    {
        return $this->entityNameResolver->getName($entity, $format, $locale);
    }
}
