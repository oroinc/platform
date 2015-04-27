<?php

namespace Oro\Bundle\CommentBundle\Model;

interface CommentCountAmountInterface
{
    public function getAmount($entityClass, $entityId);
}
