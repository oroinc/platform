<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class RemoveManyToOneRelationQuery extends RemoveRelationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getRelationType()
    {
        return RelationType::MANY_TO_ONE;
    }
}
