<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Exception;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides utility methods to work with manageable doctrine ORM entities.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DoctrineHelper implements ResetInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var ShortMetadataProvider */
    private $shortMetadataProvider;

    /** @var array */
    private $entityClasses = [];

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->entityClasses = [];
    }

    /**
     * Gets the real class name for the given entity (even if its a proxy).
     *
     * @param object $entity An entity object
     *
     * @return string
     */
    public function getClass($entity)
    {
        return $this->getRealClass(get_class($entity));
    }

    /**
     * Gets the real class name of the given entity class name that could be a proxy.
     *
     * @param string $className An entity class name or entity proxy class name
     *
     * @return string
     */
    public function getRealClass($className)
    {
        if (isset($this->entityClasses[$className])) {
            return $this->entityClasses[$className];
        }
        $realClassName = ClassUtils::getRealClass($className);
        $this->entityClasses[$className] = $realClassName;

        return $realClassName;
    }

    /**
     * Gets the real class name for the given entity, entity class name, entity proxy class name or entity type.
     *
     * @param object|string $entityOrClass An entity object, entity class name, entity proxy class name or entity type
     *
     * @return string
     */
    public function getEntityClass($entityOrClass)
    {
        if (\is_object($entityOrClass)) {
            return $this->getClass($entityOrClass);
        }
        if (str_contains($entityOrClass, ':')) {
            if (isset($this->entityClasses[$entityOrClass])) {
                return $this->entityClasses[$entityOrClass];
            }
            [$namespaceAlias, $simpleClassName] = explode(':', $entityOrClass, 2);
            $realEntityClass = $this->registry->getAliasNamespace($namespaceAlias) . '\\' . $simpleClassName;
            $this->entityClasses[$entityOrClass] = $realEntityClass;

            return $realEntityClass;
        }

        return $this->getRealClass($entityOrClass);
    }

    /**
     * Extracts the identifier values of the given entity.
     *
     * @throws Exception\NotManageableEntityException if an entity is not manageable and it doesn't have getId() method
     */
    public function getEntityIdentifier(object $entity): array
    {
        $entityClass = $this->getClass($entity);
        $manager = $this->registry->getManagerForClass($entityClass);
        if (null !== $manager) {
            return $manager->getClassMetadata($entityClass)->getIdentifierValues($entity);
        }

        if (method_exists($entity, 'getId')) {
            return ['id' => $entity->getId()];
        }

        throw new Exception\NotManageableEntityException($entityClass);
    }

    /**
     * Check whether the given entity is new entity object.
     *
     * @throws Exception\NotManageableEntityException if an entity is not manageable
     */
    public function isNewEntity(object $entity): bool
    {
        $entityClass = $this->getClass($entity);
        $manager = $this->registry->getManagerForClass($entityClass);
        if (null === $manager) {
            throw new Exception\NotManageableEntityException($entityClass);
        }

        $identifierValues = $manager->getClassMetadata($entityClass)->getIdentifierValues($entity);

        return count($identifierValues) === 0;
    }

    /**
     * Extracts the single identifier value of the given entity.
     *
     * @throws Exception\InvalidEntityException if the entity has several identifier fields and $throwException is TRUE
     */
    public function getSingleEntityIdentifier(object $entity, bool $throwException = true): mixed
    {
        $entityIdentifier = $this->getEntityIdentifier($entity);

        $result = null;
        if (count($entityIdentifier) > 1) {
            if ($throwException) {
                throw new Exception\InvalidEntityException(sprintf(
                    'Can\'t get single identifier for "%s" entity.',
                    $this->getEntityClass($entity)
                ));
            }
        } else {
            $result = $entityIdentifier ? reset($entityIdentifier) : null;
        }

        return $result;
    }

    /**
     * Gets an array of identifier field names for the given entity or class.
     *
     * @param object|string $entityOrClass  An entity object, entity class name or entity proxy class name
     * @param bool          $throwException Whether to throw exception in case the entity is not manageable
     *
     * @return string[]
     *
     * @throws Exception\InvalidEntityException
     */
    public function getEntityIdentifierFieldNames($entityOrClass, $throwException = true)
    {
        $em = $this->getEntityMetadata($entityOrClass, $throwException);

        return null !== $em
            ? $em->getIdentifierFieldNames()
            : [];
    }

    /**
     * Gets an array of identifier field names for the given entity class.
     *
     * @param string $entityClass    The real class name of an entity
     * @param bool   $throwException Whether to throw exception in case the entity is not manageable
     *
     * @return string[]
     *
     * @throws Exception\InvalidEntityException
     */
    public function getEntityIdentifierFieldNamesForClass($entityClass, $throwException = true)
    {
        $em = $this->getEntityMetadataForClass($entityClass, $throwException);

        return null !== $em
            ? $em->getIdentifierFieldNames()
            : [];
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
        return null !== $this->registry->getManagerForClass($entityClass);
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
     * Gets a brief information about manageable entities registered in a given entity manager.
     * Use this method if you need only FQCN of entities, "mapped superclass" or "has associations" flags.
     * Using of this method instead of getAllMetadata() gives significant performance gain.
     *
     * @param ObjectManager $manager        The entity manager
     * @param bool          $throwException Whether to throw exception in case if metadata cannot be retrieved
     *
     * @return ShortClassMetadata[] A brief information about manageable entities sorted by entity names
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
     * Gets the EntityManager by name.
     *
     * @throws Exception\NotManageableEntityException if the EntityManager was not found and $throwException is TRUE
     */
    public function getManager(?string $name = null): ?ObjectManager
    {
        return $this->registry->getManager($name);
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
     * Creates a new QueryBuilder instance for the given entity class.
     *
     * @param string $entityClass The real class name of an entity
     * @param string $alias       The alias of the entity class
     * @param string $indexBy     The index for the from
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($entityClass, $alias, $indexBy = null)
    {
        return $this
            ->getEntityManagerForClass($entityClass)
            ->createQueryBuilder()
            ->from($entityClass, $alias, $indexBy)
            ->select($alias);
    }

    /**
     * Gets a reference to the entity identified by the given class and identifier
     * without actually loading it, if the entity is not yet loaded.
     *
     * @param string $entityClass The class name of an entity
     * @param mixed  $entityId    The identifier of an entity
     *
     * @return object
     *
     * @template T
     * @psalm-param class-string<T> $entityClass
     * @psalm-return T
     */
    public function getEntityReference($entityClass, $entityId)
    {
        return $this
            ->getEntityManagerForClass($entityClass)
            ->getReference($entityClass, $entityId);
    }

    /**
     * Finds an entity by its identifier.
     *
     * @param string $entityClass The class name of an entity
     * @param mixed  $entityId    The identifier of an entity
     *
     * @return object|null
     *
     * @template T
     * @psalm-param class-string<T> $entityClass
     * @psalm-return T
     */
    public function getEntity($entityClass, $entityId)
    {
        return $this
            ->getEntityManagerForClass($entityClass)
            ->find($entityClass, $entityId);
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
            ->getEntityMetadataForClass($entityClass)
            ->newInstance();
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
