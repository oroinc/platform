<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Migration query for removing many-to-many relationships from entity configuration.
 *
 * This query handles the removal of many-to-many relationship definitions during database migrations,
 * cleaning up the entity configuration and related data structures when relationships are no longer needed.
 */
class RemoveManyToManyRelationQuery extends RemoveRelationQuery
{
    #[\Override]
    public function getRelationType()
    {
        return RelationType::MANY_TO_MANY;
    }
}
