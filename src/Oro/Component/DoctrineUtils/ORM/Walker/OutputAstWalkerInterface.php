<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\TreeWalker;

/**
 * Doctrine AST walker that may be registered with DI tag and used by DecoratedSqlWalkerTrait.
 * Should be used to only modify AST, returned result is not used.
 */
interface OutputAstWalkerInterface extends TreeWalker
{
    public const HINT_AST_WALKERS = 'HINT_AST_WALKERS';

    /**
     * Gets the information about a single query component.
     *
     * @param string $dqlAlias The DQL alias.
     *
     * @return array
     */
    public function getQueryComponent($dqlAlias);

    /**
     * Gets the Query instance used by the walker.
     *
     * @return AbstractQuery.
     */
    public function getQuery();

    /**
     * Gets the Connection used by the walker.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection();

    /**
     * Gets the EntityManager used by the walker.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager();
}
