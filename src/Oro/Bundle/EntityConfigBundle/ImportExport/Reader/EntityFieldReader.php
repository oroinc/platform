<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Reader;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;

class EntityFieldReader extends EntityReader
{
    /**
     * @var int
     */
    protected $entityId;

    /**
     * {@inheritdoc}
     */
    public function setSourceQueryBuilder(QueryBuilder $queryBuilder)
    {
        if ($this->entityId) {
            $aliases = $queryBuilder->getRootAliases();
            $rootAlias = reset($aliases);
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->eq(sprintf('IDENTITY(%s.entity)', $rootAlias), ':entity')
                )
                ->setParameter('entity', $this->entityId);
        }

        parent::setSourceQueryBuilder($queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->entityId = (int) $context->getOption('entity_id');

        parent::initializeFromContext($context);
    }
}
