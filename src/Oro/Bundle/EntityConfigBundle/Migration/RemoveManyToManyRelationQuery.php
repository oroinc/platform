<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class RemoveManyToManyRelationQuery extends RemoveRelationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getRelationType()
    {
        return RelationType::MANY_TO_MANY;
    }
}
