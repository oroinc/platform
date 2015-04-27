<?php

namespace Oro\Bundle\CommentBundle\Model;

interface CommentCountAmountInterface
{
    /**
     * @param $entityClass
     * @param $entityId
     * @return integer
     */
    public function getAmount($entityClass, $entityId);
}
