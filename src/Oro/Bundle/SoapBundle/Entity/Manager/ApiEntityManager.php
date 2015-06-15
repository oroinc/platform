<?php

namespace Oro\Bundle\SoapBundle\Entity\Manager;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;
use Oro\Bundle\EntityBundle\ORM\QueryBuilderHelper;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\SoapBundle\Event\FindAfter;
use Oro\Bundle\SoapBundle\Event\GetListBefore;
use Oro\Bundle\SoapBundle\Serializer\EntitySerializer;

class ApiEntityManager
{
    /** @var string */
    protected $class;

    /** @var ObjectManager */
    protected $om;

    /** @var ClassMetadata|ClassMetadataInfo */
    protected $metadata;

    /** @var EventDispatcher */
    protected $eventDispatcher;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var EntitySerializer */
    protected $entitySerializer;

    /**
     * Constructor
     *
     * @param string          $class Entity name
     * @param ObjectManager   $om Object manager
     */
    public function __construct($class, ObjectManager $om)
    {
        $this->om = $om;
        if ($class) {
            $this->setClass($class);
        }
    }

    /**
     * Sets the type of the entity this manager is responsible for
     *
     * @param string $entityName The name or the plural alias of the entity
     */
    public function setClass($entityName)
    {
        if (false === strpos($entityName, '\\')) {
            $entityName = $this->entityAliasResolver->getClassByPluralAlias($entityName);
        }

        $this->metadata = $this->om->getClassMetadata($entityName);
        $this->class    = $this->metadata->getName();
    }

    /**
     * Sets a event dispatcher
     *
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Sets the doctrine helper
     *
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Sets the entity alias resolver
     *
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function setEntityAliasResolver(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * Sets the entity serializer
     *
     * @param EntitySerializer $entitySerializer
     */
    public function setEntitySerializer(EntitySerializer $entitySerializer)
    {
        $this->entitySerializer = $entitySerializer;
    }

    /**
     * Get entity metadata
     *
     * @return ClassMetadata|ClassMetadataInfo
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Create new entity instance
     *
     * @return mixed
     */
    public function createEntity()
    {
        return new $this->class;
    }

    /**
     * Get entity by identifier.
     *
     * @param  mixed  $id
     * @return object
     */
    public function find($id)
    {
        $object = $this->getRepository()->find($id);

        if ($object) {
            $this->checkFoundEntity($object);
        }

        return $object;
    }

    /**
     * @param object $entity
     *
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    public function getEntityId($entity)
    {
        $className = $this->class;
        if (!$entity instanceof $className) {
            throw new \InvalidArgumentException('Expected instance of ' . $this->class);
        }

        $idField   = $this->metadata->getSingleIdentifierFieldName();
        $entityIds = $this->metadata->getIdentifierValues($entity);

        return $entityIds[$idField];
    }

    /**
     * Return related repository
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->class);
    }

    /**
     * Retrieve object manager
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->om;
    }

    /**
     * Returns array of item matching filtering criteria
     *
     * In case when limit and offset set to null QueryBuilder instance will be returned.
     *
     * @deprecated since 1.4.1 use getListQueryBuilder instead
     * @param int        $limit
     * @param int        $page
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return \Traversable
     */
    public function getList($limit = 10, $page = 1, $criteria = [], $orderBy = null)
    {
        $criteria = $this->prepareQueryCriteria($limit, $page, $criteria, $orderBy);

        return $this->getRepository()
            ->matching($criteria)
            ->toArray();
    }

    /**
     * Returns query builder that could be used for fetching data based on given filtering criteria
     *
     * @param int   $limit
     * @param int   $page
     * @param array $criteria
     * @param null  $orderBy
     * @param array $joins
     *
     * @return QueryBuilder|SqlQueryBuilder|SearchQuery
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $criteria = $this->prepareQueryCriteria($limit, $page, $criteria, $orderBy);

        $qb = $this->getRepository()->createQueryBuilder('e');
        $this->applyJoins($qb, $joins);

        // fix of doctrine error with Same Field, Multiple Values, Criteria and QueryBuilder
        // http://www.doctrine-project.org/jira/browse/DDC-2798
        // TODO revert changes when doctrine version >= 2.5 in scope of BAP-5577
        QueryBuilderHelper::addCriteria($qb, $criteria);
        // $qb->addCriteria($criteria);

        return $qb;
    }

    /**
     * Serializes the list of entities
     *
     * @param QueryBuilder $qb A query builder is used to get data
     *
     * @return array
     */
    public function serialize(QueryBuilder $qb)
    {
        return $this->entitySerializer->serialize($qb, $this->getSerializationConfig());
    }

    /**
     * Serializes single entity
     *
     * @param mixed $id Entity id
     *
     * @return array|null
     */
    public function serializeOne($id)
    {
        $qb = $this->getRepository()->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', $id);

        $config = $this->getSerializationConfig();
        $this->entitySerializer->prepareQuery($qb, $config);
        $entity = $qb->getQuery()->getResult();
        if (!$entity) {
            return null;
        }

        $this->checkFoundEntity($entity[0]);

        $serialized = $this->entitySerializer->serializeEntities((array)$entity, $this->class, $config);

        return $serialized[0];
    }

    /**
     * @param object $entity
     *
     * @throws AccessDeniedException if access to the given entity is denied
     */
    protected function checkFoundEntity($entity)
    {
        // dispatch oro_api.request.find.after event
        $event = new FindAfter($entity);
        $this->eventDispatcher->dispatch(FindAfter::NAME, $event);
    }

    /**
     * Indicates whether the entity serializer is configured
     *
     * @return bool
     */
    public function isSerializerConfigured()
    {
        return null !== $this->getSerializationConfig();
    }

    /**
     * Returns the configuration of the entity serializer is used for process GET reruests
     *
     * @return array|null
     */
    protected function getSerializationConfig()
    {
        return null;
    }

    /**
     * @param int   $limit
     * @param int   $page
     * @param array $criteria
     * @param null  $orderBy
     *
     * @return array|Criteria
     */
    protected function prepareQueryCriteria($limit = 10, $page = 1, $criteria = [], $orderBy = null)
    {
        $page = $page > 0 ? $page : 1;

        $criteria = $this->normalizeCriteria($criteria);

        // dispatch oro_api.request.get_list.before event
        $event = new GetListBefore($criteria, $this->class);
        $this->eventDispatcher->dispatch(GetListBefore::NAME, $event);
        $criteria = $event->getCriteria();

        $criteria
            ->setMaxResults($limit)
            ->orderBy($this->getOrderBy($orderBy))
            ->setFirstResult($this->getOffset($page, $limit));

        return $criteria;
    }

    /**
     * Checks the given criteria and converts them to Criteria object if needed
     *
     * @param Criteria|array|null $criteria
     *
     * @return Criteria
     */
    protected function normalizeCriteria($criteria)
    {
        return $this->doctrineHelper->normalizeCriteria($criteria);
    }

    /**
     * Applies the given joins for the query builder
     *
     * @param QueryBuilder $qb
     * @param array|null   $joins
     */
    protected function applyJoins($qb, $joins)
    {
        $this->doctrineHelper->applyJoins($qb, $joins);
    }

    /**
     * Get order by
     *
     * @param $orderBy
     * @return array|null
     */
    protected function getOrderBy($orderBy)
    {
        return $orderBy ?: $this->getDefaultOrderBy();
    }

    /**
     * Calculates the page offset
     *
     * @param int $page  The page number
     * @param int $limit The maximum number of items per page
     *
     * @return int
     */
    protected function getOffset($page, $limit)
    {
        return $this->doctrineHelper->getPageOffset($page, $limit);
    }

    /**
     * Get default order by.
     *
     * @return array|null
     */
    protected function getDefaultOrderBy()
    {
        $ids = $this->metadata->getIdentifierFieldNames();
        $orderBy = $ids ? [] : null;
        foreach ($ids as $pk) {
            $orderBy[$pk] = 'ASC';
        }

        return $orderBy;
    }
}
