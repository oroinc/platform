<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Provider\ChainEntityOverrideProvider;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\MutableEntityOverrideProvider;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * Provides functionality to convert an entity object to a model object and vise versa.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityMapper
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntityInstantiator */
    private $entityInstantiator;

    /** @var EntityOverrideProviderInterface */
    private $entityOverrideProvider;

    /** @var MutableEntityOverrideProvider|null */
    private $additionalEntityOverrideProvider;

    /** @var \SplObjectStorage */
    private $entityMap;

    /** @var \SplObjectStorage */
    private $modelMap;

    /** @var \SplObjectStorage */
    private $processing;

    /**
     * @param DoctrineHelper                  $doctrineHelper
     * @param EntityInstantiator              $entityInstantiator
     * @param EntityOverrideProviderInterface $entityOverrideProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityInstantiator $entityInstantiator,
        EntityOverrideProviderInterface $entityOverrideProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityInstantiator = $entityInstantiator;
        $this->entityOverrideProvider = $entityOverrideProvider;
        $this->entityMap = new \SplObjectStorage();
        $this->modelMap = new \SplObjectStorage();
        $this->processing = new \SplObjectStorage();
    }

    /**
     * Returns a model for the given entity.
     *
     * @param object      $entity
     * @param string|null $modelClass
     *
     * @return object
     *
     * @throws \InvalidArgumentException if the entity or the model class is not valid
     */
    public function getModel($entity, string $modelClass = null)
    {
        $updateReferences = $this->entityMap->offsetExists($entity)
            && null === $this->entityMap->offsetGet($entity);
        try {
            $model = $this->innerGetModel($entity, $modelClass);
            if ($updateReferences) {
                $this->processing->removeAll($this->processing);
                $this->updateReferencesToModel($model, $entity);
            }

            return $model;
        } finally {
            $this->processing->removeAll($this->processing);
        }
    }

    /**
     * Returns an entity for the given model.
     *
     * @param object      $model
     * @param string|null $entityClass
     *
     * @return object
     *
     * @throws \InvalidArgumentException if the model or the entity class is not valid
     */
    public function getEntity($model, string $entityClass = null)
    {
        try {
            return $this->innerGetEntity($model, $entityClass);
        } finally {
            $this->processing->removeAll($this->processing);
        }
    }

    /**
     * Makes sure that the given entity exists in the map.
     *
     * @param object $entity
     *
     * @throws \InvalidArgumentException if the entity is not a manageable entity
     */
    public function registerEntity($entity): void
    {
        $this->assertEntity(ClassUtils::getClass($entity));
        if (!$this->entityMap->offsetExists($entity)) {
            $this->entityMap->offsetSet($entity, null);
        }
    }

    /**
     * Adds the mapping between an entity and its model, in additional to the default mapping.
     *
     * @param string $entityClass
     * @param string $modelClass
     */
    public function mapEntity(string $entityClass, string $modelClass): void
    {
        if (null === $this->additionalEntityOverrideProvider) {
            $this->additionalEntityOverrideProvider = new MutableEntityOverrideProvider();
            $this->entityOverrideProvider = new ChainEntityOverrideProvider(
                [$this->additionalEntityOverrideProvider, $this->entityOverrideProvider]
            );
        }
        $this->additionalEntityOverrideProvider->addSubstitution($entityClass, $modelClass);
    }

    /**
     * Clears the state of the mapper.
     */
    public function clear(): void
    {
        $this->entityMap->removeAll($this->entityMap);
        $this->modelMap->removeAll($this->modelMap);
        $this->processing->removeAll($this->processing);
    }

    /**
     * @param object      $entity
     * @param string|null $modelClass
     *
     * @return object
     *
     * @throws \InvalidArgumentException
     */
    private function innerGetModel($entity, string $modelClass = null)
    {
        $entityClass = ClassUtils::getClass($entity);
        if (!$modelClass) {
            $modelClass = $this->getModelClass($entity);
        }
        $this->assertEntityAndModelClasses($entityClass, $modelClass);

        $model = null;
        if ($this->entityMap->offsetExists($entity)) {
            $model = $this->entityMap->offsetGet($entity);
        }
        if (null === $model) {
            $model = $entity;
            if ($modelClass !== $entityClass) {
                $model = $this->createObject($modelClass, $entity);
            }
            $this->entityMap->offsetSet($entity, $model);
            $this->modelMap->offsetSet($model, $entity);
            if (!$this->processing->offsetExists($entity)) {
                $this->processing->offsetSet($entity);
                $this->updateModelAssociations($model, $entity);
            }
        }

        return $model;
    }

    /**
     * @param object $model
     * @param object $entity
     */
    private function updateModelAssociations($model, $entity): void
    {
        $modelReflClass = new \ReflectionClass(ClassUtils::getClass($model));
        $entityReflClass = new \ReflectionClass(ClassUtils::getClass($entity));
        $metadata = $this->getEntityMetadata($entityReflClass->getName());
        foreach ($metadata->getAssociationNames() as $name) {
            $value = self::getObjectPropertyValue($entityReflClass, $entity, $name);
            if (null !== $value) {
                if ($metadata->isCollectionValuedAssociation($name)) {
                    if (!self::isNotInitializedLazyCollection($value)) {
                        foreach ($value as $key => $val) {
                            $valModel = $this->innerGetModel($val);
                            if ($valModel !== $val) {
                                $value[$key] = $valModel;
                            }
                        }
                    }
                } else {
                    $valueModel = $this->innerGetModel($value);
                    if ($valueModel !== $value) {
                        self::setObjectPropertyValue($modelReflClass, $model, $name, $valueModel);
                    }
                }
            }
        }
    }

    /**
     * @param object $model
     * @param object $entity
     */
    private function updateReferencesToModel($model, $entity): void
    {
        foreach ($this->modelMap as $currentModel) {
            if ($currentModel === $model) {
                continue;
            }

            $properties = self::getProperties($currentModel);
            foreach ($properties as $property) {
                $value = self::getPropertyValue($currentModel, $property);
                if ($value instanceof Collection) {
                    if (!self::isNotInitializedLazyCollection($value)) {
                        foreach ($value as $key => $val) {
                            if ($val === $entity) {
                                $value[$key] = $model;
                            }
                        }
                    }
                } else {
                    if ($value === $entity) {
                        self::setPropertyValue($currentModel, $property, $model);
                    }
                }
            }
        }
    }

    /**
     * @param object      $model
     * @param string|null $entityClass
     *
     * @return object
     *
     * @throws \InvalidArgumentException
     */
    private function innerGetEntity($model, string $entityClass = null)
    {
        $modelClass = ClassUtils::getClass($model);
        if (!$entityClass) {
            $entityClass = $this->getEntityClass($model);
        }
        $this->assertEntityAndModelClasses($entityClass, $modelClass);

        $isNewEntity = false;
        if ($this->modelMap->offsetExists($model)) {
            $entity = $this->modelMap->offsetGet($model);
        } else {
            $entity = $model;
            if ($entityClass !== $modelClass) {
                $entity = $this->createObject($entityClass, $model);
                $isNewEntity = true;
            }
            $this->modelMap->offsetSet($model, $entity);
            $this->entityMap->offsetSet($entity, $model);
        }
        if (!$this->processing->offsetExists($model)) {
            $this->processing->offsetSet($model);
            if (!self::isNotInitializedEntityProxy($entity)) {
                $this->updateEntity($entity, $model, $entityClass, $modelClass, $isNewEntity);
            }
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @param object $model
     * @param string $entityClass
     * @param string $modelClass
     * @param bool   $isNewEntity
     *
     * @throws \ReflectionException
     */
    private function updateEntity(
        $entity,
        $model,
        string $entityClass,
        string $modelClass,
        bool $isNewEntity
    ): void {
        $entityReflClass = new \ReflectionClass($entityClass);
        $metadata = $this->getEntityMetadata($entityClass, $modelClass);
        if ($entity === $model) {
            $this->refreshEntityAssociations($entityReflClass, $metadata, $entity);
        } else {
            $modelReflClass = new \ReflectionClass($modelClass);
            if (!$isNewEntity) {
                $this->updateEntityFields($entityReflClass, $modelReflClass, $metadata, $entity, $model);
            }
            $this->updateEntityAssociations($entityReflClass, $modelReflClass, $metadata, $entity, $model);
        }
    }

    /**
     * @param \ReflectionClass $entityReflClass
     * @param \ReflectionClass $modelReflClass
     * @param ClassMetadata    $metadata
     * @param object           $entity
     * @param object           $model
     */
    private function updateEntityFields(
        \ReflectionClass $entityReflClass,
        \ReflectionClass $modelReflClass,
        ClassMetadata $metadata,
        $entity,
        $model
    ): void {
        $names = $metadata->getFieldNames();
        foreach ($names as $name) {
            self::setObjectPropertyValue(
                $entityReflClass,
                $entity,
                $name,
                self::getObjectPropertyValue($modelReflClass, $model, $name)
            );
        }
    }

    /**
     * @param \ReflectionClass $entityReflClass
     * @param \ReflectionClass $modelReflClass
     * @param ClassMetadata    $metadata
     * @param object           $entity
     * @param object           $model
     */
    private function updateEntityAssociations(
        \ReflectionClass $entityReflClass,
        \ReflectionClass $modelReflClass,
        ClassMetadata $metadata,
        $entity,
        $model
    ): void {
        $names = $metadata->getAssociationNames();
        foreach ($names as $name) {
            $value = self::getObjectPropertyValue($modelReflClass, $model, $name);
            if ($metadata->isCollectionValuedAssociation($name)) {
                if (!self::isNotInitializedLazyCollection($value)) {
                    foreach ($value as $key => $val) {
                        $value[$key] = $this->innerGetEntity($val);
                    }
                }
            } else {
                if (null !== $value) {
                    $value = $this->innerGetEntity($value);
                }
                self::setObjectPropertyValue($entityReflClass, $entity, $name, $value);
            }
        }
    }

    /**
     * @param \ReflectionClass $entityReflClass
     * @param ClassMetadata    $metadata
     * @param object           $entity
     */
    private function refreshEntityAssociations(
        \ReflectionClass $entityReflClass,
        ClassMetadata $metadata,
        $entity
    ): void {
        $names = $metadata->getAssociationNames();
        foreach ($names as $name) {
            $value = self::getObjectPropertyValue($entityReflClass, $entity, $name);
            if ($metadata->isCollectionValuedAssociation($name)) {
                if (!self::isNotInitializedLazyCollection($value)) {
                    foreach ($value as $key => $val) {
                        $valEntity = $this->innerGetEntity($val);
                        if ($valEntity !== $val) {
                            $value[$key] = $valEntity;
                        }
                    }
                }
            } elseif (null !== $value) {
                $valueEntity = $this->innerGetEntity($value);
                if ($valueEntity !== $value) {
                    self::setObjectPropertyValue($entityReflClass, $entity, $name, $valueEntity);
                }
            }
        }
    }

    /**
     * Gets the model class name that should be used for the given entity.
     *
     * @param object $entity
     *
     * @return string
     */
    private function getModelClass($entity): string
    {
        $modelClass = ClassUtils::getClass($entity);
        $substituteClass = $this->entityOverrideProvider->getSubstituteEntityClass($modelClass);
        if ($substituteClass) {
            $modelClass = $substituteClass;
        }

        return $modelClass;
    }

    /**
     * Gets the entity class name for the given model.
     *
     * @param object $model
     *
     * @return string
     */
    private function getEntityClass($model): string
    {
        $modelClass = ClassUtils::getClass($model);
        $entityClass = $this->doctrineHelper->resolveManageableEntityClass($modelClass);

        return $entityClass ?? $modelClass;
    }

    /**
     * Checks if the given classes for an entity and a model
     * represent valid relationship between an entity and its model.
     *
     * @param string $entityClass
     * @param string $modelClass
     *
     * @throws \InvalidArgumentException if the entity class or the model class is not valid
     */
    private function assertEntityAndModelClasses(string $entityClass, string $modelClass): void
    {
        if ($modelClass !== $entityClass) {
            if (!\is_subclass_of($modelClass, $entityClass)) {
                throw new \InvalidArgumentException(\sprintf(
                    'The model class "%s" must be equal to or a subclass of the entity class "%s".',
                    $modelClass,
                    $entityClass
                ));
            }
            if ($this->doctrineHelper->isManageableEntityClass($modelClass)
                && !self::isParentEntityClass($this->getEntityMetadata($entityClass))
            ) {
                throw new \InvalidArgumentException(\sprintf(
                    'The model class "%s" must not represent a manageable entity.',
                    $modelClass
                ));
            }
        }
        $this->assertEntity($entityClass);
    }

    /**
     * Checks if the given entity class is a manageable entity.
     *
     * @param string $entityClass
     *
     * @throws \InvalidArgumentException if the entity class is not a manageable entity
     */
    private function assertEntity(string $entityClass): void
    {
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            throw new \InvalidArgumentException(\sprintf(
                'The entity class "%s" must represent a manageable entity.',
                $entityClass
            ));
        }
    }

    /**
     * Creates an instance of $objectClass
     * and copies values of all properties from $source to the created object.
     *
     * @param string $objectClass
     * @param object $source
     *
     * @return object
     */
    private function createObject(string $objectClass, $source)
    {
        $object = $this->entityInstantiator->instantiate($objectClass);
        $objectReflClass = new \ReflectionClass($objectClass);
        $sourceProperties = self::getProperties($source);
        foreach ($sourceProperties as $sourceProperty) {
            $objectProperty = ReflectionUtil::getProperty($objectReflClass, $sourceProperty->getName());
            if (null !== $objectProperty) {
                self::setPropertyValue(
                    $object,
                    $objectProperty,
                    self::getPropertyValue($source, $sourceProperty)
                );
            }
        }

        return $object;
    }

    /**
     * Gets ORM metadata for the given entity class.
     *
     * @param string      $entityClass
     * @param string|null $modelClass
     *
     * @return ClassMetadata
     */
    private function getEntityMetadata(string $entityClass, string $modelClass = null): ClassMetadata
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        if ($modelClass && self::isParentEntityClass($metadata)) {
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($modelClass);
        }

        return $metadata;
    }

    /**
     * Checks if an entity is a mapped supperclass or a base class for a table inheritance.
     *
     * @param ClassMetadata $metadata
     *
     * @return bool
     */
    private static function isParentEntityClass(ClassMetadata $metadata): bool
    {
        return
            $metadata->isMappedSuperclass
            || $metadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE;
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    private static function isNotInitializedEntityProxy($entity): bool
    {
        return $entity instanceof Proxy && !$entity->__isInitialized();
    }

    /**
     * @param mixed $collection
     *
     * @return bool
     */
    private static function isNotInitializedLazyCollection($collection): bool
    {
        return $collection instanceof AbstractLazyCollection && !$collection->isInitialized();
    }

    /**
     * @param object $object
     *
     * @return \ReflectionProperty[]
     */
    private static function getProperties($object): array
    {
        $reflClass = new \ReflectionClass(ClassUtils::getClass($object));
        $properties = $reflClass->getProperties();
        $parentClass = $reflClass->getParentClass();
        if ($parentClass) {
            $names = [];
            foreach ($properties as $property) {
                $names[$property->getName()] = true;
            }
            while ($parentClass) {
                $parentProperties = $parentClass->getProperties();
                foreach ($parentProperties as $parentProperty) {
                    if (!isset($names[$parentProperty->getName()])) {
                        $properties[] = $parentProperty;
                        $names[$parentProperty->getName()] = true;
                    }
                }
                $parentClass = $parentClass->getParentClass();
            }
        }

        return $properties;
    }

    /**
     * @param object              $object
     * @param \ReflectionProperty $property
     *
     * @return mixed
     */
    private static function getPropertyValue($object, \ReflectionProperty $property)
    {
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }

        return $property->getValue($object);
    }

    /**
     * @param object              $object
     * @param \ReflectionProperty $property
     * @param mixed               $value
     */
    private static function setPropertyValue($object, \ReflectionProperty $property, $value): void
    {
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }
        $property->setValue($object, $value);
    }

    /**
     * @param \ReflectionClass $objectReflClass
     * @param object           $object
     * @param string           $propertyName
     *
     * @return mixed
     */
    private static function getObjectPropertyValue(
        \ReflectionClass $objectReflClass,
        $object,
        string $propertyName
    ) {
        return self::getPropertyValue(
            $object,
            ReflectionUtil::getProperty($objectReflClass, $propertyName)
        );
    }

    /**
     * @param \ReflectionClass $objectReflClass
     * @param object           $object
     * @param string           $propertyName
     * @param mixed            $value
     */
    private static function setObjectPropertyValue(
        \ReflectionClass $objectReflClass,
        $object,
        string $propertyName,
        $value
    ): void {
        self::setPropertyValue(
            $object,
            ReflectionUtil::getProperty($objectReflClass, $propertyName),
            $value
        );
    }
}
