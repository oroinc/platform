<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Event\AfterEntityPageLoadedEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class EntityReader extends IteratorBasedReader
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadata;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var AclHelper */
    protected $aclHelper;

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
        $this->setSourceIterator($this->createSourceIterator($queryBuilder));
    }

    /**
     * @param Query $query
     */
    public function setSourceQuery(Query $query)
    {
        $this->setSourceIterator($this->createSourceIterator($query));
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

        $this->applyAcl($queryBuilder);

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
     * @param AclHelper $aclHelper
     */
    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
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

    /**
     * @param Query|QueryBuilder $source
     *
     * @return \Iterator
     */
    protected function createSourceIterator($source)
    {
        return (new BufferedQueryResultIterator($source))
            ->setPageLoadedCallback(function (array $rows) {
                if (!$this->dispatcher->hasListeners(Events::AFTER_ENTITY_PAGE_LOADED)) {
                    return $rows;
                }

                $event = new AfterEntityPageLoadedEvent($rows);
                $this->dispatcher->dispatch(Events::AFTER_ENTITY_PAGE_LOADED, $event);

                return $event->getRows();
            });
    }

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     */
    protected function applyAcl(QueryBuilder $queryBuilder)
    {
        if ($this->aclHelper) {
            return $this->aclHelper->apply($queryBuilder);
        }

        return $queryBuilder;
    }
}
