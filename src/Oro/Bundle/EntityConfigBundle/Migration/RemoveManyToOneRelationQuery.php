<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

class RemoveManyToOneRelationQuery extends RemoveRelationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getRelationType()
    {
        return RemoveRelationQuery::MANY_TO_ONE;
    }
}
