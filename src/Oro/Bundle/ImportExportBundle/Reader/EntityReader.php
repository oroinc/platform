<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

class EntityReader extends IteratorBasedReader
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ContextRegistry $contextRegistry
     * @param ManagerRegistry $registry
     */
    public function __construct(ContextRegistry $contextRegistry, ManagerRegistry $registry)
    {
        parent::__construct($contextRegistry);

        $this->registry = $registry;
    }

    /**
     * @param ContextInterface $context
     * @throws InvalidConfigurationException
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if ($context->hasOption('entityName')) {
            $this->setSourceEntityName($context->getOption('entityName'));
        } elseif ($context->hasOption('queryBuilder')) {
            $this->setSourceQueryBuilder($context->getOption('queryBuilder'));
        } elseif ($context->hasOption('query')) {
            $this->setSourceQuery($context->getOption('query'));
        } elseif (!$this->getSourceIterator()) {
            throw new InvalidConfigurationException(
                'Configuration of entity reader must contain either "entityName", "queryBuilder" or "query".'
            );
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function setSourceQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->setSourceIterator(new BufferedQueryResultIterator($queryBuilder));
    }

    /**
     * @param Query $query
     */
    public function setSourceQuery(Query $query)
    {
        $this->setSourceIterator(new BufferedQueryResultIterator($query));
    }

    /**
     * @param string $entityName
     */
    public function setSourceEntityName($entityName)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry->getRepository($entityName)->createQueryBuilder('o');

        $metadata = $qb->getEntityManager()->getClassMetadata($entityName);
        foreach ($metadata->getAssociationMappings() as $assocMapping) {
            $alias = '_' . $assocMapping['fieldName'];
            $qb->addSelect($alias);
            $qb->leftJoin('o.' . $assocMapping['fieldName'], $alias);
        }

        $this->setSourceQueryBuilder($qb);
    }
}
