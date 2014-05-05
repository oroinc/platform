<?php

namespace Oro\Bundle\IntegrationBundle\Reader;


use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader as BaseReader;

class EntityReaderWithFilters extends BaseReader
{

    public function __construct(ContextRegistry $contextRegistry, ManagerRegistry $registry)
    {
        parent::__construct($contextRegistry, $registry);
    }

    protected function initializeFromContext(ContextInterface $context)
    {
        if ($context->hasOption('entityName')) {

            $filter = [];

            if ($context->hasOption('id')) {
                $filter = ['id'=>$context->getOption('id')];
            }

            $this->setSourceEntityName($context->getOption('entityName'), $filter);

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

    public function setSourceEntityName($entityName, $filter = [])
    {
        $qb = $this->getQueryBuilder($entityName);

        $metadata = $qb->getEntityManager()->getClassMetadata($entityName);

        foreach ($metadata->getAssociationMappings() as $assocMapping) {
            $alias = '_' . $assocMapping['fieldName'];
            $qb->addSelect($alias);
            $qb->leftJoin('o.' . $assocMapping['fieldName'], $alias);
        }

        if (!empty($filter)) {

            foreach ($filter as $key => $row) {
                if (is_array($row)) {
                    $qb->add('where', $qb->expr()->in('o.'.$key, ':'.$key))->setParameter($key, $row);
                } else {
                    $qb->add('where', 'o.'.$key.' = :'.$key)->setParameter($key, $row);
                }

            }
        }

        foreach ($metadata->getIdentifierFieldNames() as $fieldName) {
            $qb->orderBy('o.' . $fieldName, 'ASC');
        }

        $this->setSourceQueryBuilder($qb);
    }

    /**
     * @param string $entityName
     *
     * @return  QueryBuilder $qb
     */
    protected function getQueryBuilder($entityName)
    {
        return $this->registry->getRepository($entityName)->createQueryBuilder('o');
    }
}
