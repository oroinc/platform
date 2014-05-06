<?php

namespace Oro\Bundle\IntegrationBundle\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader as BaseReader;

class EntityReaderById extends BaseReader
{
    const ID_FILTER = 'id';

    /**
     * {@inheritdoc}
     */
    public function __construct(ContextRegistry $contextRegistry, ManagerRegistry $registry)
    {
        parent::__construct($contextRegistry, $registry);
    }

    /**
     * {@inheritdoc}
     */
    public function setSourceEntityName($context)
    {
        $entityName = $context->getOption('entityName');
        $qb         = $this->getQueryBuilder($entityName);
        $metadata   = $qb->getEntityManager()->getClassMetadata($entityName);

        foreach ($metadata->getAssociationMappings() as $assocMapping) {
            $alias = '_' . $assocMapping['fieldName'];
            $qb->addSelect($alias);
            $qb->leftJoin('o.' . $assocMapping['fieldName'], $alias);
        }

        if ($context->hasOption(self::ID_FILTER)) {
            $optionValue = $context->getOption(self::ID_FILTER);

            if (is_array($optionValue)) {
                $qb->add(
                    'where',
                    $qb->expr()->in('o.'.self::ID_FILTER, ':'.self::ID_FILTER)
                );
                $qb->setParameter(self::ID_FILTER, $optionValue);
            } else {
                $qb->add('where', 'o.'.self::ID_FILTER.' = :'.self::ID_FILTER);
                $qb->setParameter(self::ID_FILTER, $optionValue);
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

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if ($context->hasOption('entityName')) {
            $this->setSourceEntityName($context);
        } elseif (!$this->getSourceIterator()) {
            throw new InvalidConfigurationException(
                'Configuration of entity reader must contain either "entityName".'
            );
        }
    }
}
