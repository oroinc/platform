<?php

namespace Oro\Bundle\CommentBundle\Model;

interface CommentProviderInterface
{
    /**
     * Checks whether a given entity can have comments
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function isCommentsEnabled($entityClass);
}
