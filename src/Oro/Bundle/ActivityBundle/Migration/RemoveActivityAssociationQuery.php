<?php
declare(strict_types=1);

namespace Oro\Bundle\ActivityBundle\Migration;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveAssociationQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Removes activity association from an entity and updates entity config.
 */
class RemoveActivityAssociationQuery extends RemoveAssociationQuery
{
    public function __construct(string $activityClass, string $targetEntityClass, bool $dropRelationColumnsAndTables)
    {
        $this->sourceEntityClass = $activityClass;
        $this->targetEntityClass = $targetEntityClass;
        $this->associationKind = ActivityScope::ASSOCIATION_KIND;
        $this->relationType = RelationType::MANY_TO_MANY;
        $this->dropRelationColumnsAndTables = $dropRelationColumnsAndTables;
    }
}
