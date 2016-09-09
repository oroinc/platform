<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\EntityBundle\Exception;

class DoctrineHelper
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ShortMetadataProvider
     */
    private $shortMetadataProvider;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Gets a real class name for an entity.
     *
     * @param object|string $entityOrClass An entity object, entity class name or entity proxy class name
     *
     * @return string
     */
    public function getEntityClass($entityOrClass)
    {
        if (is_object($entityOrClass)) {
            return ClassUtils::getClass($entityOrClass);
        }

        if (strpos($entityOrClass, ':') !== false) {
            list($namespaceAlias, $simpleClassName) = explode(':', $entityOrClass, 2);
            return $this->registry->getAliasNamespace($namespaceAlias) . '\\' . $simpleClassName;
        }

        return ClassUtils::getRealClass($entityOrClass);
    }

    /**
     * Extracts the identifier values of the given entity.
     *
     * @param object $entity An entity object
     *
     * @return array
     */
    public function getEntityIdentifier($entity)
    {
        // check if we can use getId method to fast get the identifier
        if (method_exists($entity, 'getId')) {
            // @todo This code doesn't support composite keys.
            return ['id' => $entity->getId()];
        }

        return $this
            ->getEntityMetadata($entity)
            ->getIdentifierValues($entity);
    }

    /**
     * Check whether an entity is new
     *
     * @param object $entity An entity object
     *
     * @return bool
     */
    public function isNewEntity($entity)
    {
        $identifierValues = $this->getEntityMetadata($entity)->getIdentifierValues($entity);

        return count($identifierValues) === 0;
    }

    /**
     * Gets the root entity alias of the query.
     *
     * @param QueryBuilder $qb
     * @param bool         $throwException
     *
     * @return string|null
     *
     * @throws Exception\InvalidEntityException
     *
     * @deprecated since 1.9. Use QueryUtils::getSingleRootAlias instead
     */
    public function getSingleRootAlias(QueryBuilder $qb, $throwException = true)
    {
        return QueryUtils::getSingleRootAlias($qb, $throwException);
    }

    /**
     * Extracts the single identifier value of the given entity.
     *
     * @param object $entity         An entity object
     * @param bool   $throwException Whether to throw exception in case the entity has several identifier fields
     *
     * @return mixed|null
     *
     * @throws Exception\InvalidEntityException
     */
    public function getSingleEntityIdentifier($entity, $throwException = true)
    {
        $entityIdentifier = $this->getEntityIdentifier($entity);

        $result = null;
        if (count($entityIdentifier) > 1) {
            if ($throwException) {
                throw new Exception\InvalidEntityException(
                    sprintf(
                        'Can\'t get single identifier for "%s" entity.',
                        $this->getEntityClass($entity)
                    )
                );
            }
        } else {
            $result = $entityIdentifier ? reset($entityIdentifier) : null;
        }

        return $result;
    }

    /**
     * Gets an array of identifier field names for the given entity or class.
     *
     * @param object|string $entityOrClass An entity object, entity class name or entity proxy class name
     *
     * @return string[]
     */
    public function getEntityIdentifierFieldNames($entityOrClass)
    {
        return $this
            ->getEntityMetadata($entityOrClass)
            ->getIdentifierFieldNames();
    }

    /**
     * Gets an array of identifier field names for the given entity class.
     *
     * @param string $entityClass The real class name of an entity
     *
     * @return string[]
     */
    public function getEntityIdentifierFieldNamesForClass($entityClass)
    {
        return $this
            ->getEntityMetadataForClass($entityClass)
            ->getIdentifierFieldNames();
    }

    /**
     * Gets the name of the single id field.
     *
     * @param object|string $entityOrClass  An entity object, entity class name or entity proxy class name
     * @param bool          $throwException Whether to throw exception in case the entity has several identifier fields
     *
     * @return string|null
     *
     * @throws Exception\InvalidEntityException
     */
    public function getSingleEntityIdentifierFieldName($entityOrClass, $throwException = true)
    {
        $fieldNames = $this->getEntityIdentifierFieldNames($entityOrClass);

        $result = null;
        if (count($fieldNames) > 1) {
            if ($throwException) {
                throw new Exception\InvalidEntityException(
                    sprintf(
                        'Can\'t get single identifier field name for "%s" entity.',
                        $this->getEntityClass($entityOrClass)
                    )
                );
            }
        } else {
            $result = $fieldNames ? reset($fieldNames) : null;
        }

        return $result;
    }

    /**
     * Gets the type of the single id field.
     *
     * @param object|string $entityOrClass  An entity object, entity class name or entity proxy class name
     * @param bool          $throwException Whether to throw exception in case the entity has several identifier fields
     *
     * @return string|null
     *
     * @throws Exception\InvalidEntityException
     */
    public function getSingleEntityIdentifierFieldType($entityOrClass, $throwException = true)
    {
        $metadata   = $this->getEntityMetadata($entityOrClass);
        $fieldNames = $metadata->getIdentifierFieldNames();

        $result = null;
        if (count($fieldNames) !== 1) {
            if ($throwException) {
                throw new Exception\InvalidEntityException(
                    sprintf(
                        'Can\'t get single identifier field type for "%s" entity.',
                        $this->getEntityClass($entityOrClass)
                    )
                );
            }
        } else {
            $result = $metadata->getTypeOfField(reset($fieldNames));
        }

        return $result;
    }

    /**
     * Checks whether the given entity or class is manageable.
     *
     * @param object|string $entityOrClass An entity object, entity class name or entity proxy class name
     *
     * @return bool
     */
    public function isManageableEntity($entityOrClass)
    {
        return null !== $this->getEntityManager($entityOrClass, false);
    }

    /**
     * Checks whether the given class is manageable entity.
     *
     * @param string $entityClass The real class name of an entity
     *
     * @return bool
     */
    public function isManageableEntityClass($entityClass)
    {
        return null !== $this->getEntityManagerForClass($entityClass, false);
    }

    /**
     * Gets the ORM metadata descriptor for the given entity or class.
     *
     * @param object|string $entityOrClass  An entity object, entity class name or entity proxy class name
     * @param bool          $throwException Whether to throw exception in case the entity is not manageable
     *
     * @return ClassMetadata|null
     *
     * @throws Exception\NotManageableEntityException if the EntityManager was not found and $throwException is TRUE
     */
    public function getEntityMetadata($entityOrClass, $throwException = true)
    {
        return $this->getEntityMetadataForClass(
            $this->getEntityClass($entityOrClass),
            $throwException
        );
    }

    /**
     * Gets the ORM metadata descriptor for the given entity class.
     *
     * @param string $entityClass    The real class name of an entity
     * @param bool   $throwException Whether to throw exception in case the entity is not manageable
     *
     * @return ClassMetadata|null
     *
     * @throws Exception\NotManageableEntityException if the EntityManager was not found and $throwException is TRUE
     */
    public function getEntityMetadataForClass($entityClass, $throwException = true)
    {
        $manager = $this->registry->getManagerForClass($entityClass);
        if (null === $manager && $throwException) {
            throw new Exception\NotManageableEntityException($entityClass);
        }

        return null !== $manager
            ? $manager->getClassMetadata($entityClass)
            : null;
    }

    /**
     * Gets short form of metadata for all entities registered in a given entity manager.
     * Use this method if you need only FQCN of entities and "mapped superclass" flag.
     * Using of this method instead of getAllMetadata() gives significant performance gain.
     *
     * @param ObjectManager $manager        The entity manager
     * @param bool          $throwException Whether to throw exception in case if metadata cannot be retrieved
     *
     * @return ShortClassMetadata[]
     */
    public function getAllShortMetadata(ObjectManager $manager, $throwException = true)
    {
        if (null === $this->shortMetadataProvider) {
            $this->shortMetadataProvider = $this->createShortMetadataProvider();
        }

        return $this->shortMetadataProvider->getAllShortMetadata($manager, $throwException);
    }

    /**
     * Gets the EntityManager associated with the given entity or class.
     *
     * @param object|string $entityOrClass  An entity object, entity class name or entity proxy class name
     * @param bool          $throwException Whether to throw exception in case the entity is not manageable
     *
     * @return EntityManager|null
     *
     * @throws Exception\NotManageableEntityException if the EntityManager was not found and $throwException is TRUE
     */
    public function getEntityManager($entityOrClass, $throwException = true)
    {
        return $this->getEntityManagerForClass(
            $this->getEntityClass($entityOrClass),
            $throwException
        );
    }

    /**
     * Gets the EntityManager associated with the given class.
     *
     * @param string $entityClass    The real class name of an entity
     * @param bool   $throwException Whether to throw exception in case the entity is not manageable
     *
     * @return EntityManager|null
     *
     * @throws Exception\NotManageableEntityException if the EntityManager was not found and $throwException is TRUE
     */
    public function getEntityManagerForClass($entityClass, $throwException = true)
    {
        $manager = $this->registry->getManagerForClass($entityClass);
        if (null === $manager && $throwException) {
            throw new Exception\NotManageableEntityException($entityClass);
        }

        return $manager;
    }

    /**
     * Gets the repository for the given entity or class.
     *
     * @param object|string $entityOrClass An entity object, entity class name or entity proxy class name
     *
     * @return EntityRepository
     */
    public function getEntityRepository($entityOrClass)
    {
        return $this->getEntityRepositoryForClass(
            $this->getEntityClass($entityOrClass)
        );
    }

    /**
     * Gets the repository for the given entity class.
     *
     * @param string $entityClass The real class name of an entity
     *
     * @return EntityRepository
     */
    public function getEntityRepositoryForClass($entityClass)
    {
        return $this
            ->getEntityManagerForClass($entityClass)
            ->getRepository($entityClass);
    }

    /**
     * Gets a reference to the entity identified by the given class and identifier
     * without actually loading it, if the entity is not yet loaded.
     *
     * @param string $entityClass The class name of an entity
     * @param mixed  $entityId    The identifier of an entity
     *
     * @return object
     */
    public function getEntityReference($entityClass, $entityId)
    {
        return $this
            ->getEntityManager($entityClass)
            ->getReference($entityClass, $entityId);
    }

    /**
     * Finds an entity by its identifier.
     *
     * @param string $entityClass The class name of an entity
     * @param mixed  $entityId    The identifier of an entity
     *
     * @return object|null
     */
    public function getEntity($entityClass, $entityId)
    {
        return $this
            ->getEntityRepository($entityClass)
            ->find($entityId);
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @param string $entityClass The class name of an entity
     *
     * @return object
     */
    public function createEntityInstance($entityClass)
    {
        return $this
            ->getEntityMetadata($entityClass)
            ->newInstance();
    }

    /**
     * Calculates the page offset
     *
     * @param int $page  The page number
     * @param int $limit The maximum number of items per page
     *
     * @return int
     *
     * @deprecated since 1.9. Use QueryUtils::getPageOffset instead
     */
    public function getPageOffset($page, $limit)
    {
        return QueryUtils::getPageOffset($page, $limit);
    }

    /**
     * Applies the given joins for the query builder
     *
     * @param QueryBuilder $qb
     * @param array|null   $joins
     *
     * @deprecated since 1.9. Use QueryUtils::applyJoins instead
     */
    public function applyJoins(QueryBuilder $qb, $joins)
    {
        QueryUtils::applyJoins($qb, $joins);
    }

    /**
     * Checks the given criteria and converts them to Criteria object if needed
     *
     * @param Criteria|array|null $criteria
     *
     * @return Criteria
     *
     * @deprecated since 1.9. Use QueryUtils::normalizeCriteria instead
     */
    public function normalizeCriteria($criteria)
    {
        return QueryUtils::normalizeCriteria($criteria);
    }

    /**
     * Works the way like refresh on EntityManager.
     * In addition it makes sure all relations with cascade persist are also refreshed.
     *
     * @param object $entity
     */
    public function refreshIncludingUnitializedRelations($entity)
    {
        $em = $this->getEntityManager($entity);
        $em->refresh($entity);

        $metadata = $this->getEntityMetadata($entity);
        $associationMappings = array_filter(
            $metadata->associationMappings,
            function ($assoc) {
                return $assoc['isCascadeRefresh'];
            }
        );

        foreach ($associationMappings as $assoc) {
            $relatedEntities = $metadata->reflFields[$assoc['fieldName']]->getValue($entity);
            if (!$relatedEntities instanceof PersistentCollection || $relatedEntities->isInitialized()) {
                continue;
            }

            foreach ($relatedEntities as $relatedEntity) {
                $em->refresh($relatedEntity);
            }
        }
    }

    /**
     * @return ShortMetadataProvider
     */
    protected function createShortMetadataProvider()
    {
        return new ShortMetadataProvider();
    }
}
