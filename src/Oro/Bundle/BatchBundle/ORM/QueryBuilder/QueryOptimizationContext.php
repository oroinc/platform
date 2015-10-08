<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class QueryOptimizationContext
{
    /** @var QueryBuilder */
    protected $originalQueryBuilder;

    /** @var QueryBuilder */
    protected $optimizedQueryBuilder;

    /** @var QueryBuilderTools */
    protected $qbTools;

    /** @var ClassMetadataFactory */
    private $metadataFactory;

    /**
     * @param QueryBuilder      $queryBuilder
     * @param QueryBuilderTools $qbTools
     */
    public function __construct(QueryBuilder $queryBuilder, QueryBuilderTools $qbTools)
    {
        // make sure 'from' DQL part is initialized for both original and optimized query builders
        $queryBuilder->getRootEntities();

        $this->originalQueryBuilder  = $queryBuilder;
        $this->optimizedQueryBuilder = clone $queryBuilder;
        $this->qbTools               = $qbTools;

        $this->metadataFactory = $this->originalQueryBuilder->getEntityManager()->getMetadataFactory();
        // make sure that metadata factory is initialized
        $this->metadataFactory->getAllMetadata();

        // initialize the query builder helper
        $this->qbTools->prepareFieldAliases($this->originalQueryBuilder->getDQLPart('select'));
        $this->qbTools->prepareJoinTablePaths($this->originalQueryBuilder->getDQLPart('join'));
    }

    /**
     * @return QueryBuilder
     */
    public function getOriginalQueryBuilder()
    {
        return $this->originalQueryBuilder;
    }

    /**
     * @return QueryBuilder
     */
    public function getOptimizedQueryBuilder()
    {
        return $this->optimizedQueryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function setOptimizedQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->optimizedQueryBuilder = $queryBuilder;
    }

    /**
     * @param string $className
     *
     * @return ClassMetadata
     */
    public function getClassMetadata($className)
    {
        return $this->metadataFactory->getMetadataFor($className);
    }
}
