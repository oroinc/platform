<?php

namespace Oro\Bundle\IntegrationBundle\Reader;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader as BaseReader;

class EntityReaderById extends BaseReader
{
    const ID_FILTER = 'id';

    /** @var QueryBuilder */
    protected $qb;

    /**
     * {@inheritdoc}
     */
    public function setSourceQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->qb = $queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if ($context->hasOption('entityName')) {
            $this->setSourceEntityName(
                $context->getOption('entityName'),
                $context->getOption('organization'),
                $this->getIdsFromContext($context)
            );
        } else {
            parent::initializeFromContext($context);
        }

        $this->ensureInitialized($context);
    }

    /**
     * Ensure that filtering applied and query builder wrapped in buffered iterator
     * if data source is query builder or just an entity name
     *
     * @param ContextInterface $context
     */
    protected function ensureInitialized(ContextInterface $context)
    {
        if (null !== $this->qb) {
            if ($context->hasOption(self::ID_FILTER)) {
                $optionValue = $context->getOption(self::ID_FILTER);
                $em          = $this->qb->getEntityManager();
                $entityNames = $this->qb->getRootEntities();

                $classMetadata = $em->getClassMetadata(reset($entityNames));
                $identifier    = $classMetadata->getSingleIdentifierFieldName();

                if (is_array($optionValue)) {
                    $this->qb->andWhere(
                        $this->qb->expr()->in('o.' . $identifier, ':id')
                    );
                    $this->qb->setParameter('id', $optionValue);
                } else {
                    $this->qb->andWhere('o.' . $identifier . ' = :id');
                }

                $this->qb->setParameter('id', $optionValue);
            }

            $this->setSourceIterator(new BufferedIdentityQueryResultIterator($this->qb));
        }
    }

    /**
     * @param ContextInterface $context
     * @return int[]
     */
    protected function getIdsFromContext(ContextInterface $context)
    {
        $ids = $context->getOption('ids', []);

        if ($context->hasOption('id')) {
            $id = $context->getOption('id');

            if (is_array($id)) {
                $ids = array_unique(array_merge($ids, $id));
            } else {
                if (!in_array($id, $ids)) {
                    array_push($ids, $context->getOption('id'));
                }
            }
        }

        return $ids;
    }
}
