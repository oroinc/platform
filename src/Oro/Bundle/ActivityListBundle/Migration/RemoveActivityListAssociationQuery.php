<?php
declare(strict_types=1);

namespace Oro\Bundle\ActivityListBundle\Migration;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveAssociationQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Removes activity list association from an entity and updates entity config.
 */
class RemoveActivityListAssociationQuery extends RemoveAssociationQuery
{
    public function __construct(string $targetEntityClass, bool $dropRelationColumnsAndTables)
    {
        $this->sourceEntityClass = ActivityList::class;
        $this->targetEntityClass = $targetEntityClass;
        $this->associationKind = ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND;
        $this->relationType = RelationType::MANY_TO_MANY;
        $this->dropRelationColumnsAndTables = $dropRelationColumnsAndTables;
    }
}
