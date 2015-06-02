<?php

namespace Oro\Bundle\IntegrationBundle\Reader;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

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
        parent::initializeFromContext($context);

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

            $this->setSourceIterator(new BufferedQueryResultIterator($this->qb));
        }
    }
}
