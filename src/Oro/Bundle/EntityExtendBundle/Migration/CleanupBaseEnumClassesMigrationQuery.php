<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

/**
 * Cleanup base enums query.
 */
class CleanupBaseEnumClassesMigrationQuery extends AbstractCleanupMarketingMigrationQuery
{
    public function __construct(private readonly array $classes)
    {
    }

    public function getClassNames(): array
    {
        return $this->classes;
    }
}
