<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Event\AfterEntityPageLoadedEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityReader extends IteratorBasedReader implements BatchIdsReaderInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadata;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param ContextRegistry                    $contextRegistry
     * @param ManagerRegistry                    $registry
     * @param OwnershipMetadataProviderInterface $ownershipMetadata
     */
    public function __construct(
        ContextRegistry $contextRegistry,
        ManagerRegistry $registry,
        OwnershipMetadataProviderInterface $ownershipMetadata
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
            $this->setSourceEntityName(
                $context->getOption('entityName'),
                $context->getOption('organization'),
                $context->getOption('ids', [])
            );
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
     * @param string $entityName
     * @param Organization $organization
     * @param array $ids
     */
    public function setSourceEntityName($entityName, Organization $organization = null, array $ids = [])
    {
        $qb = $this->createSourceEntityQueryBuilder($entityName, $organization, $ids);
        $this->setSourceQuery($this->applyAcl($qb));
    }


    /**
     * @param $entityName
     * @param Organization|null $organization
     * @param array $ids
     *
     * @return QueryBuilder
     */
    protected function createSourceEntityQueryBuilder($entityName, Organization $organization = null, array $ids = [])
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry
            ->getManagerForClass($entityName);

        $qb = $entityManager
            ->getRepository($entityName)
            ->createQueryBuilder('o');

        $metadata = $entityManager->getClassMetadata($entityName);
        foreach (array_keys($metadata->getAssociationMappings()) as $fieldName) {
            // can't join with *-to-many relations because they affects query pagination
            if ($metadata->isAssociationWithSingleJoinColumn($fieldName)) {
                $alias = '_' . $fieldName;
                $qb->addSelect($alias);
                $qb->leftJoin('o.' . $fieldName, $alias);
            }
        }

        foreach ($identifierNames = $metadata->getIdentifierFieldNames() as $fieldName) {
            $qb->orderBy('o.' . $fieldName, 'ASC');
        }
        if (!empty($ids)) {
            if (count($identifierNames) > 1) {
                throw new \LogicException(sprintf(
                    'not supported entity (%s) with composite primary key.',
                    $entityName
                ));
            }
            $identifierName = 'o.' . current($identifierNames);

            if (count($ids) === 1) {
                $qb
                    ->andWhere($identifierName . ' = :id')
                    ->setParameter('id', reset($ids));
            } else {
                $qb
                    ->andWhere($identifierName . ' IN (:ids)')
                    ->setParameter('ids', $ids);
            }
        }

        $this->addOrganizationLimits($qb, $entityName, $organization);

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getIds($entityName, array $options = [])
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry
            ->getManagerForClass($entityName);

        $metadata = $entityManager->getClassMetadata($entityName);

        if (count($identifierNames = $metadata->getIdentifierFieldNames()) > 1) {
            throw new \LogicException(sprintf(
                'Not supported entity (%s) with composite primary key.',
                $entityName
            ));
        }

        $identifierName = $metadata->getSingleIdentifierFieldName();
        $queryBuilder = $entityManager
            ->getRepository($entityName)
            ->createQueryBuilder('o ', 'o.' . $identifierName);
        $queryBuilder->select(sprintf('partial o.{%s}', $identifierName));
        $queryBuilder->orderBy('o.' . $identifierName, 'ASC');

        $organization = isset($options['organization']) ? $options['organization'] : null;
        $this->addOrganizationLimits($queryBuilder, $entityName, $organization);
        $this->applyAcl($queryBuilder);
        $result = $queryBuilder->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_keys($result);
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
            $organizationField = $this->ownershipMetadata->getMetadata($entityName)->getOrganizationFieldName();
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
        return (new BufferedIdentityQueryResultIterator($source))
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
     * @return Query
     */
    protected function applyAcl(QueryBuilder $queryBuilder)
    {
        if ($this->aclHelper) {
            return $this->aclHelper->apply($queryBuilder);
        }

        return $queryBuilder->getQuery();
    }
}
