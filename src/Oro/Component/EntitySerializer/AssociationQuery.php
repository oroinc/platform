<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\QueryBuilder;

/**
 * Represents ORM query that should be used to load an association data.
 *
 * IMPORTANT: the query builder must follow the following rules:
 * * it must have at least 2 aliases, "e" and "r"
 * * "e" alias must correspond to the owning entity of the association
 * * "r" alias must correspond to the target entity of the association
 *
 * Example:
 * <code>
 *  $qb = $this->doctrineHelper
 *      ->createQueryBuilder(User::class, 'r')
 *      ->innerJoin(
 *          'r.groups',
 *          'e',
 *          Join::WITH,
 *          'r.enabled = :user_enabled'
 *      )
 *      ->setParameter(':user_enabled', true);
 *
 *  $associationQuery = new AssociationQuery($qb, User::class);
 * </code>
 */
class AssociationQuery
{
    private QueryBuilder $qb;
    private string $targetEntityClass;
    private bool $collection;

    public function __construct(QueryBuilder $qb, string $targetEntityClass, bool $collection = true)
    {
        $this->qb = $qb;
        $this->targetEntityClass = $targetEntityClass;
        $this->collection = $collection;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }

    public function getTargetEntityClass(): string
    {
        return $this->targetEntityClass;
    }

    /**
     * Indicates whether this query represents to-many or to-one association.
     */
    public function isCollection(): bool
    {
        return $this->collection;
    }
}
