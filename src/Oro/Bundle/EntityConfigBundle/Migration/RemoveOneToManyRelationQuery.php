<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class RemoveOneToManyRelationQuery extends RemoveRelationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getRelationType()
    {
        return RelationType::ONE_TO_MANY;
    }
}
