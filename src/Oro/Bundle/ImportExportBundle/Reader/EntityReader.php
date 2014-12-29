<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class EntityReader extends IteratorBasedReader
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var OwnershipMetadataProvider
     */
    protected $ownershipMetadata;

    /**
     * @param ContextRegistry           $contextRegistry
     * @param ManagerRegistry           $registry
     * @param OwnershipMetadataProvider $ownershipMetadata
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        ManagerRegistry $registry,
        OwnershipMetadataProvider $ownershipMetadata
    ) {
        parent::__construct($contextRegistry);

        $this->ownershipMetadata = $ownershipMetadata;
        $this->registry = $registry;
    }

    /**
     * @param ContextInterface $context
     * @throws InvalidConfigurationException
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if ($context->hasOption('entityName')) {
            $this->setSourceEntityName($context->getOption('entityName'), $context->getOption('organization'));
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
     * @param string       $entityName
     * @param Organization $organization
     */
    public function setSourceEntityName($entityName, Organization $organization = null)
    {
        /** @var QueryBuilder $qb */
        $queryBuilder = $this->registry->getRepository($entityName)->createQueryBuilder('o');

        $metadata = $queryBuilder->getEntityManager()->getClassMetadata($entityName);
        foreach (array_keys($metadata->getAssociationMappings()) as $fieldName) {
            // can't join with *-to-many relations because they affects query pagination
            if ($metadata->isAssociationWithSingleJoinColumn($fieldName)) {
                $alias = '_' . $fieldName;
                $queryBuilder->addSelect($alias);
                $queryBuilder->leftJoin('o.' . $fieldName, $alias);
            }
        }

        foreach ($metadata->getIdentifierFieldNames() as $fieldName) {
            $queryBuilder->orderBy('o.' . $fieldName, 'ASC');
        }

        // Limit data with current organization
        if ($organization) {
            $organizationField = $this->ownershipMetadata->getMetadata($entityName)->getOrganizationFieldName();
            if ($organizationField) {
                $queryBuilder->andWhere('o.' . $organizationField . ' = :organization')
                    ->setParameter('organization', $organization);
            }

        }

        $this->setSourceQueryBuilder($queryBuilder);
    }
}
