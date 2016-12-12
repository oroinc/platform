<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

class RemoveManyToManyRelationQuery extends RemoveRelationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getRelationType()
    {
        return RemoveRelationQuery::MANY_TO_MANY;
    }
}
