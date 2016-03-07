<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\ReadEntityEvent;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class EntityReader extends IteratorBasedReader
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadata;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

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
     * {@inheritdoc}
     */
    public function read()
    {
        $object = parent::read();
        if ($object && $this->dispatcher && $this->dispatcher->hasListeners(Events::AFTER_READ_ENTITY)) {
            $event = new ReadEntityEvent($object);
            $this->dispatcher->dispatch(Events::AFTER_READ_ENTITY, $event);

            return $event->getObject();
        }

        return $object;
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
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry
            ->getManagerForClass($entityName);

        $queryBuilder = $entityManager
            ->getRepository($entityName)
            ->createQueryBuilder('o');

        $metadata = $entityManager->getClassMetadata($entityName);
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

        $this->addOrganizationLimits($queryBuilder, $entityName, $organization);

        $this->setSourceQueryBuilder($queryBuilder);
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Limit data with current organization
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $entityName
     * @param Organization $organization
     */
    protected function addOrganizationLimits(QueryBuilder $queryBuilder, $entityName, Organization $organization = null)
    {
        if ($organization) {
            $organizationField = $this->ownershipMetadata->getMetadata($entityName)->getGlobalOwnerFieldName();
            if ($organizationField) {
                $queryBuilder->andWhere('o.' . $organizationField . ' = :organization')
                    ->setParameter('organization', $organization);
            }
        }
    }
}
