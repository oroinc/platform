<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\QueryBuilder;

/**
 * Represents ORM query that should be used to load an association data.
 *
 * IMPORTANT: the query builder must follow the following rules:
 * * it must have at least 2 aliases, "e" and "r"
 * * "e" alias must be a root alias and it must correspond an entity that is an owner of the association
 * * "r" alias must correspond a target entity of the association
 *
 * Example:
 * <code>
 *  $qb = $this->doctrineHelper
 *      ->createQueryBuilder(Group::class, 'e')
 *      ->innerJoin(
 *          User::class,
 *          'r',
 *          Join::WITH,
 *          'e MEMBER OF r.groups AND r.enabled = :user_enabled'
 *      )
 *      ->setParameter(':user_enabled', true);
 *
 *  $associationQuery = new AssociationQuery($qb, User::class);
 * </code>
 */
class AssociationQuery
{
    /** @var QueryBuilder */
    private $qb;

    /** @var string */
    private $targetEntityClass;

    /**
     * @param QueryBuilder $qb
     * @param string       $targetEntityClass
     */
    public function __construct(QueryBuilder $qb, string $targetEntityClass)
    {
        $this->qb = $qb;
        $this->targetEntityClass = $targetEntityClass;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }

    /**
     * @return string
     */
    public function getTargetEntityClass(): string
    {
        return $this->targetEntityClass;
    }
}
