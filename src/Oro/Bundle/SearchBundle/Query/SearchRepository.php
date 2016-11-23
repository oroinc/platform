<?php

namespace Oro\Bundle\SearchBundle\Query;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;

class SearchRepository
{
    /**
     * @var QueryFactoryInterface
     */
    private $queryFactory;

    /**
     * @var AbstractSearchMappingProvider
     */
    private $mappingProvider;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var array
     */
    protected $queryConfiguration = [];

    /**
     * @param QueryFactoryInterface $queryFactory
     * @param AbstractSearchMappingProvider $mappingProvider
     */
    public function __construct(
        QueryFactoryInterface $queryFactory,
        AbstractSearchMappingProvider $mappingProvider
    ) {
        $this->queryFactory = $queryFactory;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @return SearchQueryInterface
     */
    public function createQuery()
    {
        $query = $this->getQueryFactory()->create($this->queryConfiguration);

        if ($this->entityName) {
            $entityAlias = $this->getMappingProvider()->getEntityAlias($this->entityName);
            $query->setFrom($entityAlias);
        }

        return $query;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param string $entityName
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * @return QueryFactoryInterface
     */
    public function getQueryFactory()
    {
        return $this->queryFactory;
    }

    /**
     * @return AbstractSearchMappingProvider
     */
    public function getMappingProvider()
    {
        return $this->mappingProvider;
    }
}
