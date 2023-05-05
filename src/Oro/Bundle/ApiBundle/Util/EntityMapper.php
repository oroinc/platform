<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Proxy;
use Oro\Bundle\ApiBundle\Provider\ChainEntityOverrideProvider;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\MutableEntityOverrideProvider;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * Provides functionality to convert an entity object to a model object and vise versa.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityMapper
{
    private DoctrineHelper $doctrineHelper;
    private EntityInstantiator $entityInstantiator;
    private EntityOverrideProviderInterface $entityOverrideProvider;
    private \SplObjectStorage $entityMap;
    private \SplObjectStorage $modelMap;
    private \SplObjectStorage $processing;
    private ?MutableEntityOverrideProvider $additionalEntityOverrideProvider = null;

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
     * @throws \InvalidArgumentException if the entity or the model class is not valid
     */
    public function getModel(object $entity, string $modelClass = null): object
    {
        $updateReferences =
            $this->entityMap->offsetExists($entity)
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
     * @throws \InvalidArgumentException if the model or the entity class is not valid
     */
    public function getEntity(object $model, string $entityClass = null): object
    {
        if (!$this->hasModels()) {
            $modelClass = $this->doctrineHelper->getClass($model);
            if (!$entityClass) {
                $entityClass = $this->getEntityClass($modelClass);
            }
            if ($modelClass === $entityClass) {
                if (!$this->modelMap->offsetExists($model)) {
                    $this->modelMap->offsetSet($model, $model);
                    $this->entityMap->offsetSet($model, $model);
                }

                return $model;
            }
        }
        try {
            return $this->innerGetEntity($model, $entityClass);
        } finally {
            $this->processing->removeAll($this->processing);
        }
    }

    /**
     * Makes sure that the given entity exists in the map.
     *
     * @throws \InvalidArgumentException if the entity is not a manageable entity
     */
    public function registerEntity(object $entity): void
    {
        $this->assertEntity($this->doctrineHelper->getClass($entity));
        if (!$this->entityMap->offsetExists($entity)) {
            $this->entityMap->offsetSet($entity, null);
        }
    }

    /**
     * Adds the mapping between an entity and its model, in additional to the default mapping.
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
     * @throws \InvalidArgumentException
     */
    private function innerGetModel(object $entity, string $modelClass = null): object
    {
        $model = null;
        if ($this->entityMap->offsetExists($entity)) {
            $model = $this->entityMap->offsetGet($entity);
        }
        if (null === $model) {
            $model = $entity;
            $entityClass = $this->doctrineHelper->getClass($entity);
            if (!$modelClass) {
                $modelClass = $this->getModelClass($entityClass);
            }
            if ($modelClass !== $entityClass) {
                $this->assertEntityAndModelClasses($entityClass, $modelClass);
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

    private function updateModelAssociations(object $model, object $entity): void
    {
        $modelReflClass = new EntityReflectionClass($this->doctrineHelper->getClass($model));
        $entityReflClass = new EntityReflectionClass($this->doctrineHelper->getClass($entity));
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

    private function updateReferencesToModel(object $model, object $entity): void
    {
        foreach ($this->modelMap as $currentModel) {
            if ($currentModel === $model) {
                continue;
            }

            $properties = self::getProperties($this->doctrineHelper->getClass($currentModel));
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
                } elseif ($value === $entity) {
                    self::setPropertyValue($currentModel, $property, $model);
                }
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function innerGetEntity(object $model, string $entityClass = null): object
    {
        $modelClass = null;
        $isNewEntity = false;
        if ($this->modelMap->offsetExists($model)) {
            $entity = $this->modelMap->offsetGet($model);
        } else {
            $entity = $model;
            $modelClass = $this->doctrineHelper->getClass($model);
            if (!$entityClass) {
                $entityClass = $this->getEntityClass($modelClass);
            }
            if ($entityClass !== $modelClass) {
                $this->assertEntityAndModelClasses($entityClass, $modelClass);
                $entity = $this->createObject($entityClass, $model);
                $isNewEntity = true;
            }
            $this->modelMap->offsetSet($model, $entity);
            $this->entityMap->offsetSet($entity, $model);
        }
        if (!$this->processing->offsetExists($model)) {
            $this->processing->offsetSet($model);
            if (!self::isNotInitializedEntityProxy($entity)) {
                if (!$modelClass) {
                    $modelClass = $this->doctrineHelper->getClass($model);
                }
                if (!$entityClass) {
                    $entityClass = $this->getEntityClass($modelClass);
                }
                $this->updateEntity($entity, $model, $entityClass, $modelClass, $isNewEntity);
            }
        }

        return $entity;
    }

    /**
     * @throws \ReflectionException
     */
    private function updateEntity(
        object $entity,
        object $model,
        string $entityClass,
        string $modelClass,
        bool $isNewEntity
    ): void {
        $entityReflClass = new EntityReflectionClass($entityClass);
        $metadata = $this->getEntityMetadata($entityClass, $modelClass);
        if ($entity === $model) {
            $this->refreshEntityAssociations($entityReflClass, $metadata, $entity);
        } else {
            $modelReflClass = new EntityReflectionClass($modelClass);
            if (!$isNewEntity) {
                $this->updateEntityFields($entityReflClass, $modelReflClass, $metadata, $entity, $model);
            }
            $this->updateEntityAssociations($entityReflClass, $modelReflClass, $metadata, $entity, $model);
        }
    }

    private function updateEntityFields(
        \ReflectionClass $entityReflClass,
        \ReflectionClass $modelReflClass,
        ClassMetadata $metadata,
        object $entity,
        object $model
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

    private function updateEntityAssociations(
        \ReflectionClass $entityReflClass,
        \ReflectionClass $modelReflClass,
        ClassMetadata $metadata,
        object $entity,
        object $model
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

    private function refreshEntityAssociations(
        \ReflectionClass $entityReflClass,
        ClassMetadata $metadata,
        object $entity
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
     */
    private function getModelClass(string $entityClass): string
    {
        $substituteClass = $this->entityOverrideProvider->getSubstituteEntityClass($entityClass);
        if ($substituteClass) {
            return $substituteClass;
        }

        return $entityClass;
    }

    /**
     * Gets the entity class name for the given model.
     */
    private function getEntityClass(string $modelClass): string
    {
        return $this->doctrineHelper->resolveManageableEntityClass($modelClass) ?? $modelClass;
    }

    /**
     * Checks if the given classes for an entity and a model
     * represent valid relationship between an entity and its model.
     *
     * @throws \InvalidArgumentException if the entity class or the model class is not valid
     */
    private function assertEntityAndModelClasses(string $entityClass, string $modelClass): void
    {
        if (!is_subclass_of($modelClass, $entityClass)) {
            throw new \InvalidArgumentException(sprintf(
                'The model class "%s" must be equal to or a subclass of the entity class "%s".',
                $modelClass,
                $entityClass
            ));
        }
        if ($this->doctrineHelper->isManageableEntityClass($modelClass)
            && !self::isParentEntityClass($this->getEntityMetadata($entityClass))
        ) {
            throw new \InvalidArgumentException(sprintf(
                'The model class "%s" must not represent a manageable entity.',
                $modelClass
            ));
        }
        $this->assertEntity($entityClass);
    }

    /**
     * Checks if the given entity class is a manageable entity.
     *
     * @throws \InvalidArgumentException if the entity class is not a manageable entity
     */
    private function assertEntity(string $entityClass): void
    {
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            throw new \InvalidArgumentException(sprintf(
                'The entity class "%s" must represent a manageable entity.',
                $entityClass
            ));
        }
    }

    /**
     * Creates an instance of $objectClass
     * and copies values of all properties from $source to the created object.
     */
    private function createObject(string $objectClass, object $source): object
    {
        $object = $this->entityInstantiator->instantiate($objectClass);
        $objectReflClass = new EntityReflectionClass($objectClass);
        $sourceProperties = self::getProperties($this->doctrineHelper->getClass($source));
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
     */
    private static function isParentEntityClass(ClassMetadata $metadata): bool
    {
        return
            $metadata->isMappedSuperclass
            || !$metadata->isInheritanceTypeNone();
    }

    private static function isNotInitializedEntityProxy(mixed $entity): bool
    {
        return $entity instanceof Proxy && !$entity->__isInitialized();
    }

    private static function isNotInitializedLazyCollection(mixed $collection): bool
    {
        return $collection instanceof AbstractLazyCollection && !$collection->isInitialized();
    }

    /**
     * @param string $objectClass
     *
     * @return \ReflectionProperty[]
     */
    private static function getProperties(string $objectClass): array
    {
        $reflClass = new EntityReflectionClass($objectClass);
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

    private static function getPropertyValue(mixed $object, \ReflectionProperty $property): mixed
    {
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }

        return $property->getValue($object);
    }

    private static function setPropertyValue(object $object, \ReflectionProperty $property, mixed $value): void
    {
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }
        $property->setValue($object, $value);
    }

    private static function getObjectPropertyValue(
        \ReflectionClass $objectReflClass,
        object $object,
        string $propertyName
    ): mixed {
        return self::getPropertyValue(
            $object,
            ReflectionUtil::getProperty($objectReflClass, $propertyName)
        );
    }

    private static function setObjectPropertyValue(
        \ReflectionClass $objectReflClass,
        object $object,
        string $propertyName,
        mixed $value
    ): void {
        self::setPropertyValue(
            $object,
            ReflectionUtil::getProperty($objectReflClass, $propertyName),
            $value
        );
    }

    private function hasModels(): bool
    {
        foreach ($this->modelMap as $model) {
            if ($this->modelMap->offsetGet($model) !== $model) {
                return true;
            }
        }

        return false;
    }
}
