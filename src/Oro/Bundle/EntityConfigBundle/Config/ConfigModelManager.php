<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper as ToolConfigHelper;

/**
 * IMPORTANT: A performance of this class is very crucial, be careful during a refactoring.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigModelManager
{
    private ManagerRegistry $doctrine;
    private LockObject $lockObject;
    private ConfigDatabaseChecker $databaseChecker;
    /** @var EntityConfigModel[]|null [{class name} => EntityConfigModel, ...] */
    private ?array $entities = null;
    private bool $entitiesAreLoaded = false;
    /** @var array [{class name} => [{field name} => FieldConfigModel, ...], ...] */
    private array $fields = [];

    public function __construct(
        ManagerRegistry $doctrine,
        LockObject $lockObject,
        ConfigDatabaseChecker $databaseChecker
    ) {
        $this->doctrine = $doctrine;
        $this->lockObject = $lockObject;
        $this->databaseChecker = $databaseChecker;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(EntityConfigModel::class);
    }

    public function checkDatabase(): bool
    {
        return $this->databaseChecker->checkDatabase();
    }

    public function clearCheckDatabase(): void
    {
        $this->databaseChecker->clearCheckDatabase();
    }

    /**
     * Finds a model for an entity.
     */
    public function findEntityModel(string $className): ?EntityConfigModel
    {
        if (empty($className) || ToolConfigHelper::isConfigModelEntity($className)) {
            return null;
        }

        $this->ensureEntityCacheWarmed($className);

        $result = null;

        // check if a model exists in the local cache
        if (null !== $this->entities && \array_key_exists($className, $this->entities)) {
            $result = $this->entities[$className];
            if ($result && $this->isEntityDetached($result)) {
                if ($this->areAllEntitiesDetached()) {
                    // reload all models because all of them are detached
                    $this->clearCache();
                    $result = $this->findEntityModel($className);
                } else {
                    // the detached model must be reloaded
                    $result = false;

                    $this->entities[$className] = null;
                    unset($this->fields[$className]);
                }
            }
        }

        // load a model if it was not found in the local cache
        if ($result === false) {
            $result = $this->loadEntityModel($className);

            $this->entities[$className] = $result;
        }

        return $result;
    }

    /**
     * Finds a model for an entity field.
     */
    public function findFieldModel(string $className, string $fieldName): ?FieldConfigModel
    {
        if (empty($className) || empty($fieldName) || ToolConfigHelper::isConfigModelEntity($className)) {
            return null;
        }

        $this->ensureFieldCacheWarmed($className);

        $result = null;

        // check if a model exists in the local cache
        if (isset($this->fields[$className]) && \array_key_exists($fieldName, $this->fields[$className])) {
            $result = $this->fields[$className][$fieldName];
            if ($result && $this->isEntityDetached($result)) {
                // the detached model must be reloaded
                $this->entities[$className] = false;
                unset($this->fields[$className]);

                $result = $this->findFieldModel($className, $fieldName);
            }
        }

        return $result;
    }

    /**
     * @throws \InvalidArgumentException if $className is empty
     * @throws RuntimeException if a model was not found
     */
    public function getEntityModel(string $className): EntityConfigModel
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }

        $model = $this->findEntityModel($className);
        if (!$model) {
            throw new RuntimeException(sprintf(
                'A model for "%s" was not found.%s',
                $className,
                $this->lockObject->isLocked() ? ' Config models are locked.' : ''
            ));
        }

        return $model;
    }

    /**
     * @throws \InvalidArgumentException if $className or $fieldName is empty
     * @throws RuntimeException if a model was not found
     */
    public function getFieldModel(string $className, string $fieldName): FieldConfigModel
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('$fieldName must not be empty');
        }

        $model = $this->findFieldModel($className, $fieldName);
        if (!$model) {
            throw new RuntimeException(sprintf(
                'A model for "%s::%s" was not found.%s',
                $className,
                $fieldName,
                $this->lockObject->isLocked() ? ' Config models are locked.' : ''
            ));
        }

        return $model;
    }

    /**
     * Renames a field.
     * Important: this method do not save changes in a database. To do this you need to call entityManager->flush().
     *
     * @param string $className
     * @param string $fieldName
     * @param string $newFieldName
     *
     * @return bool TRUE if the name was changed; otherwise, FALSE
     *
     * @throws \InvalidArgumentException if $className, $fieldName or $newFieldName is empty
     * @throws RuntimeException if models are locked
     */
    public function changeFieldName(string $className, string $fieldName, string $newFieldName): bool
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('$fieldName must not be empty');
        }
        if (empty($newFieldName)) {
            throw new \InvalidArgumentException('$newFieldName must not be empty');
        }
        if ($this->lockObject->isLocked()) {
            throw new RuntimeException(sprintf(
                'Cannot change field name for "%s::%s" because config models are locked.',
                $className,
                $fieldName
            ));
        }

        $result = false;
        $fieldModel = $this->findFieldModel($className, $fieldName);
        if ($fieldModel && $fieldModel->getFieldName() !== $newFieldName) {
            $fieldModel->setFieldName($newFieldName);
            $this->getEntityManager()->persist($fieldModel);
            unset($this->fields[$className][$fieldName]);

            $this->fields[$className][$newFieldName] = $fieldModel;
            $result = true;
        }

        return $result;
    }

    /**
     * Changes a type of a field.
     * Important: this method do not save changes in a database. To do this you need to call entityManager->flush().
     *
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     *
     * @return bool TRUE if the type was changed; otherwise, FALSE
     *
     * @throws \InvalidArgumentException if $className, $fieldName or $fieldType is empty
     * @throws RuntimeException if models are locked
     */
    public function changeFieldType(string $className, string $fieldName, string $fieldType): bool
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('$fieldName must not be empty');
        }
        if (empty($fieldType)) {
            throw new \InvalidArgumentException('$fieldType must not be empty');
        }
        if ($this->lockObject->isLocked()) {
            throw new RuntimeException(sprintf(
                'Cannot change field type for "%s::%s" because config models are locked.',
                $className,
                $fieldName
            ));
        }

        $result = false;
        $fieldModel = $this->findFieldModel($className, $fieldName);
        if ($fieldModel && $fieldModel->getType() !== $fieldType) {
            $fieldModel->setType($fieldType);
            $this->getEntityManager()->persist($fieldModel);

            $this->fields[$className][$fieldName] = $fieldModel;
            $result = true;
        }

        return $result;
    }

    /**
     * Changes a mode of a field.
     * Important: this method do not save changes in a database. To do this you need to call entityManager->flush().
     *
     * @param string $className
     * @param string $fieldName
     * @param string $mode Can be the value of one of ConfigModel::MODE_* constants
     *
     * @return bool TRUE if the mode was changed; otherwise, FALSE
     *
     * @throws \InvalidArgumentException if $className, $fieldName or $mode is empty
     * @throws RuntimeException if models are locked
     */
    public function changeFieldMode(string $className, string $fieldName, string $mode): bool
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('$fieldName must not be empty');
        }
        if (empty($mode)) {
            throw new \InvalidArgumentException('$mode must not be empty');
        }
        if ($this->lockObject->isLocked()) {
            throw new RuntimeException(sprintf(
                'Cannot change field mode for "%s::%s" because config models are locked.',
                $className,
                $fieldName
            ));
        }

        $result = false;
        $fieldModel = $this->findFieldModel($className, $fieldName);
        if ($fieldModel && $fieldModel->getMode() !== $mode) {
            $fieldModel->setMode($mode);
            $this->getEntityManager()->persist($fieldModel);

            $this->fields[$className][$fieldName] = $fieldModel;
            $result = true;
        }

        return $result;
    }

    /**
     * Changes a mode of an entity.
     * Important: this method do not save changes in a database. To do this you need to call entityManager->flush().
     *
     * @param string $className
     * @param string $mode Can be the value of one of ConfigModel::MODE_* constants
     *
     * @return bool TRUE if the type was changed; otherwise, FALSE
     *
     * @throws \InvalidArgumentException if $className or $mode is empty
     * @throws RuntimeException if models are locked
     */
    public function changeEntityMode(string $className, string $mode): bool
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }
        if (empty($mode)) {
            throw new \InvalidArgumentException('$mode must not be empty');
        }
        if ($this->lockObject->isLocked()) {
            throw new RuntimeException(sprintf(
                'Cannot change entity name for "%s" because config models are locked.',
                $className
            ));
        }

        $result = false;
        $entityModel = $this->findEntityModel($className);
        if ($entityModel && $entityModel->getMode() !== $mode) {
            $entityModel->setMode($mode);
            $this->getEntityManager()->persist($entityModel);

            $this->entities[$className] = $entityModel;
            $result = true;
        }

        return $result;
    }

    /**
     * @param string|null $className
     *
     * @return ConfigModel[]
     */
    public function getModels(string $className = null): array
    {
        $result = [];

        if ($className) {
            $this->ensureFieldCacheWarmed($className);
            foreach ($this->fields[$className] as $model) {
                if ($model) {
                    $result[] = $model;
                }
            }
        } else {
            $this->ensureEntityCacheWarmed();
            foreach ($this->entities as $model) {
                if ($model) {
                    $result[] = $model;
                }
            }
        }

        return $result;
    }

    /**
     * @throws \InvalidArgumentException if $mode is invalid
     * @throws RuntimeException if models are locked
     */
    public function createEntityModel(
        string $className = null,
        string $mode = ConfigModel::MODE_DEFAULT
    ): EntityConfigModel {
        if (!$this->isValidMode($mode)) {
            throw new \InvalidArgumentException(sprintf('Invalid $mode: "%s"', $mode));
        }
        if ($this->lockObject->isLocked()) {
            throw new RuntimeException(sprintf(
                'Cannot create entity model for "%s" because config models are locked.',
                $className
            ));
        }

        $entityModel = new EntityConfigModel($className);
        $entityModel->setMode($mode);

        if (!empty($className)) {
            $this->ensureEntityCacheWarmed();
            $this->entities[$className] = $entityModel;
        }

        return $entityModel;
    }

    /**
     * @throws \InvalidArgumentException if $className is empty or $mode is invalid
     * @throws RuntimeException if models are locked
     */
    public function createFieldModel(
        string $className,
        string $fieldName,
        string $fieldType,
        string $mode = ConfigModel::MODE_DEFAULT
    ): FieldConfigModel {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }
        if (!$this->isValidMode($mode)) {
            throw new \InvalidArgumentException(sprintf('Invalid $mode: "%s"', $mode));
        }
        if ($this->lockObject->isLocked()) {
            throw new RuntimeException(sprintf(
                'Cannot create field model for "%s::%s" because config models are locked.',
                $className,
                $fieldName
            ));
        }

        $entityModel = $this->getEntityModel($className);

        $fieldModel = new FieldConfigModel($fieldName, $fieldType);
        $fieldModel->setMode($mode);
        $entityModel->addField($fieldModel);

        if (!empty($fieldName)) {
            $this->ensureFieldCacheWarmed($className);
            $this->fields[$className][$fieldName] = $fieldModel;
        }

        return $fieldModel;
    }

    /**
     * Removes all cached data.
     */
    public function clearCache(): void
    {
        $this->entities = null;
        $this->entitiesAreLoaded = false;
        $this->fields = [];

        $em = $this->getEntityManager();
        $em->clear(FieldConfigModel::class);
        $em->clear(EntityConfigModel::class);
    }

    /**
     * Makes sure that an entity model for the given class is loaded
     * or, if the class name is not specified, make sure that all entity models are loaded.
     */
    private function ensureEntityCacheWarmed(string $className = null): void
    {
        if ($this->lockObject->isLocked()) {
            return;
        }

        if (null === $this->entities) {
            $this->entities = [];
        }
        if ($className) {
            if (!\array_key_exists($className, $this->entities)) {
                $this->entities[$className] = !$this->entitiesAreLoaded
                    ? $this->loadEntityModel($className)
                    : null;
            }
        } elseif (!$this->entitiesAreLoaded) {
            $entityModels = $this->loadEntityModels();
            foreach ($entityModels as $model) {
                $this->entities[$model->getClassName()] = $model;
            }
            $this->entitiesAreLoaded = true;
        }
    }

    /**
     * Makes sure that an entity field models for the given class are loaded.
     */
    private function ensureFieldCacheWarmed(string $className): void
    {
        if (!isset($this->fields[$className])) {
            $this->fields[$className] = [];

            $entityModel = $this->findEntityModel($className);
            if ($entityModel) {
                $fields = $entityModel->getFields();
                foreach ($fields as $model) {
                    $this->fields[$className][$model->getFieldName()] = $model;
                }
            }
        }
    }

    /**
     * @return EntityConfigModel[]
     */
    private function loadEntityModels(): array
    {
        $alreadyLoadedIds = [];
        foreach ($this->entities as $model) {
            if ($model) {
                $alreadyLoadedIds[] = $model->getId();
            }
        }

        if (empty($alreadyLoadedIds)) {
            return $this->getEntityManager()
                ->getRepository(EntityConfigModel::class)
                ->findAll();
        }

        return $this->getEntityManager()
            ->getRepository(EntityConfigModel::class)
            ->createQueryBuilder('e')
            ->where('e.id NOT IN (:exclusions)')
            ->setParameter('exclusions', $alreadyLoadedIds)
            ->getQuery()
            ->getResult();
    }

    private function loadEntityModel(string $className): ?EntityConfigModel
    {
        return $this->getEntityManager()
            ->getRepository(EntityConfigModel::class)
            ->findOneBy(['className' => $className]);
    }

    /**
     * Determines whether the given string represents a valid mode for entity config model.
     */
    private function isValidMode(string $mode): bool
    {
        return \in_array(
            $mode,
            [ConfigModel::MODE_DEFAULT, ConfigModel::MODE_HIDDEN, ConfigModel::MODE_READONLY],
            true
        );
    }

    /**
     * Determines whether all entities in local cache are detached from an entity manager or not.
     */
    private function areAllEntitiesDetached(): bool
    {
        $result = false;
        if (!empty($this->entities)) {
            $result = true;
            foreach ($this->entities as $model) {
                if ($model && !$this->isEntityDetached($model)) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Determines whether the given entity is managed by an entity manager or not.
     */
    private function isEntityDetached(object $entity): bool
    {
        $entityState = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityState($entity);

        return UnitOfWork::STATE_DETACHED === $entityState;
    }
}
