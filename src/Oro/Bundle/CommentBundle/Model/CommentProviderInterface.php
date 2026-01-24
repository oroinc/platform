<?php

namespace Oro\Bundle\CommentBundle\Model;

/**
 * Defines the contract for determining whether comments are enabled for a given entity class.
 *
 * Implementations of this interface are responsible for checking if a specific entity class
 * supports comments functionality. This is typically used to determine whether to display
 * comment-related UI elements or allow comment operations on entities.
 */
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
