<?php

namespace Oro\Bundle\CommentBundle\Model;

interface CommentLogicGetCountInterface
{
    public function getCount($entityClass, $entityId);
}
