<?php

namespace Oro\Bundle\SoapBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;

interface EntitySerializerManagerInterface
{
    /**
     * @param QueryBuilder $qb A query builder is used to get data
     *
     * @return array
     */
    public function serialize(QueryBuilder $qb);

    /**
     * @param mixed $id Entity id
     *
     * @return array|null
     */
    public function serializeOne($id);
}
