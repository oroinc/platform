<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigModelManager
{
    /**
     * mode of config model
     */
    const MODE_DEFAULT  = 'default';
    const MODE_HIDDEN   = 'hidden';
    const MODE_READONLY = 'readonly';

    /**
     * @var EntityConfigModel[]
     *
     * {class name} => EntityConfigModel
     */
    protected $entityLocalCache;

    /**
     * @var array of FieldConfigModel[]
     *
     * {class name} => array of FieldConfigModel[]
     *      {field name} => FieldConfigModel
     */
    protected $fieldLocalCache;

    /**
     * @var bool
     */
    protected $dbCheckCache;

    /**
     * @var ServiceLink
     */
    protected $proxyEm;

    private $requiredTables = array(
        'oro_entity_config',
        'oro_entity_config_field',
        'oro_entity_config_index_value',
    );

    /**
     * @param ServiceLink $proxyEm
     */
    public function __construct(ServiceLink $proxyEm)
    {
        $this->proxyEm = $proxyEm;
        $this->clearCache();
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->proxyEm->getService();
    }

    /**
     * @return bool
     */
    public function checkDatabase()
    {
        if ($this->dbCheckCache === null) {
            $this->dbCheckCache = false;
            try {
                $conn = $this->getEntityManager()->getConnection();

                if (!$conn->isConnected()) {
                    $conn->connect();
                }
                if ($conn->isConnected()) {
                    $sm                 = $conn->getSchemaManager();
                    $this->dbCheckCache = $sm->tablesExist($this->requiredTables);
                }
            } catch (\PDOException $e) {
            }
        }

        return $this->dbCheckCache;
    }

    public function clearCheckDatabase()
    {
        $this->dbCheckCache = null;
    }

    /**
     * Finds a model for an entity
     *
     * @param string $className
     * @return EntityConfigModel|null An instance of EntityConfigModel or null if a model was not found
     */
    public function findEntityModel($className)
    {
        if (empty($className) || ConfigHelper::isConfigModelEntity($className)) {
            return null;
        }

        $this->ensureEntityLocalCacheWarmed();

        $result = null;

        // check if a model exists in the local cache
        if (isset($this->entityLocalCache[$className]) || array_key_exists($className, $this->entityLocalCache)) {
            $result = $this->entityLocalCache[$className];
            if ($result && $this->isEntityDetached($result)) {
                if ($this->areAllEntitiesDetached()) {
                    // reload all models because all of them are detached
                    $this->clearCache();
                    $result = $this->findEntityModel($className);
                } else {
                    // the detached model must be reloaded
                    $result = false;

                    $this->entityLocalCache[$className] = null;
                    unset($this->fieldLocalCache[$className]);
                }
            }
        }

        // load a model if it was not found in the local cache
        if ($result === false) {
            $result = $this->loadEntityModel($className);
        }

        return $result;
    }

    /**
     * Finds a model for an entity field
     *
     * @param string $className
     * @param string $fieldName
     * @return FieldConfigModel|null An instance of FieldConfigModel or null if a model was not found
     */
    public function findFieldModel($className, $fieldName)
    {
        if (empty($className) || empty($fieldName) || ConfigHelper::isConfigModelEntity($className)) {
            return null;
        }

        $this->ensureFieldLocalCacheWarmed($className);

        $result = null;

        // check if a model exists in the local cache
        if (isset($this->fieldLocalCache[$className][$fieldName])
            || (
                isset($this->fieldLocalCache[$className])
                && array_key_exists($fieldName, $this->fieldLocalCache[$className])
            )
        ) {
            $result = $this->fieldLocalCache[$className][$fieldName];
            if ($result && $this->isEntityDetached($result)) {
                // the detached model must be reloaded
                $this->entityLocalCache[$className] = false;
                unset($this->fieldLocalCache[$className]);

                $result = $this->findFieldModel($className, $fieldName);
            }
        }

        return $result;
    }

    /**
     * @param string $className
     * @return EntityConfigModel
     * @throws \InvalidArgumentException if $className is empty
     * @throws RuntimeException if a model was not found
     */
    public function getEntityModel($className)
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }

        $model = $this->findEntityModel($className);
        if (!$model) {
            throw new RuntimeException(
                sprintf('A model for "%s" was not found', $className)
            );
        }

        return $model;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return FieldConfigModel
     * @throws \InvalidArgumentException if $className or $fieldName is empty
     * @throws RuntimeException if a model was not found
     */
    public function getFieldModel($className, $fieldName)
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('$fieldName must not be empty');
        }

        $model = $this->findFieldModel($className, $fieldName);
        if (!$model) {
            throw new RuntimeException(
                sprintf('A model for "%s::%s" was not found', $className, $fieldName)
            );
        }

        return $model;
    }

    /**
     * Renames a field
     * Important: this method do not save changes in a database. To do this you need to call entityManager->flush
     *
     * @param string $className
     * @param string $fieldName
     * @param string $newFieldName
     * @throws \InvalidArgumentException if $className, $fieldName or $newFieldName is empty
     * @return bool TRUE if the name was changed; otherwise, FALSE
     */
    public function changeFieldName($className, $fieldName, $newFieldName)
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

        $result     = false;
        $fieldModel = $this->findFieldModel($className, $fieldName);
        if ($fieldModel && $fieldModel->getFieldName() !== $newFieldName) {
            $fieldModel->setFieldName($newFieldName);
            $this->getEntityManager()->persist($fieldModel);
            unset($this->fieldLocalCache[$className][$fieldName]);

            $this->fieldLocalCache[$className][$newFieldName] = $fieldModel;
            $result                                           = true;
        }

        return $result;
    }

    /**
     * Changes a type of a field
     * Important: this method do not save changes in a database. To do this you need to call entityManager->flush
     *
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @throws \InvalidArgumentException if $className, $fieldName or $fieldType is empty
     * @return bool TRUE if the type was changed; otherwise, FALSE
     */
    public function changeFieldType($className, $fieldName, $fieldType)
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

        $result     = false;
        $fieldModel = $this->findFieldModel($className, $fieldName);
        if ($fieldModel && $fieldModel->getType() !== $fieldType) {
            $fieldModel->setType($fieldType);
            $this->getEntityManager()->persist($fieldModel);

            $this->fieldLocalCache[$className][$fieldName] = $fieldModel;
            $result                                        = true;
        }

        return $result;
    }

    /**
     * @param ConfigIdInterface $configId
     * @return AbstractConfigModel
     */
    public function getModelByConfigId(ConfigIdInterface $configId)
    {
        return $configId instanceof FieldConfigId
            ? $this->getFieldModel($configId->getClassName(), $configId->getFieldName())
            : $this->getEntityModel($configId->getClassName());
    }

    /**
     * @param string|null $className
     * @return AbstractConfigModel[]
     */
    public function getModels($className = null)
    {
        $result = [];

        if ($className) {
            $this->ensureFieldLocalCacheWarmed($className);
            foreach ($this->fieldLocalCache[$className] as $model) {
                if ($model) {
                    $result[] = $model;
                }
            }
        } else {
            $this->ensureEntityLocalCacheWarmed();
            foreach ($this->entityLocalCache as $model) {
                if ($model) {
                    $result[] = $model;
                }
            }
        }

        return $result;
    }

    /**
     * @param string|null $className
     * @param string|null $mode
     * @return EntityConfigModel
     * @throws \InvalidArgumentException
     */
    public function createEntityModel($className = null, $mode = self::MODE_DEFAULT)
    {
        if (!in_array($mode, array(self::MODE_DEFAULT, self::MODE_HIDDEN, self::MODE_READONLY))) {
            throw new \InvalidArgumentException(sprintf('Invalid $mode: "%s"', $mode));
        }

        $entityModel = new EntityConfigModel($className);
        $entityModel->setMode($mode);

        if (!empty($className)) {
            $this->ensureEntityLocalCacheWarmed();
            $this->entityLocalCache[$className] = $entityModel;
        }

        return $entityModel;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param string $mode
     * @return FieldConfigModel
     * @throws \InvalidArgumentException
     */
    public function createFieldModel($className, $fieldName, $fieldType, $mode = self::MODE_DEFAULT)
    {
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }
        if (!in_array($mode, array(self::MODE_DEFAULT, self::MODE_HIDDEN, self::MODE_READONLY))) {
            throw new \InvalidArgumentException(sprintf('Invalid $mode: "%s"', $mode));
        }

        $entityModel = $this->getEntityModel($className);

        $fieldModel = new FieldConfigModel($fieldName, $fieldType);
        $fieldModel->setMode($mode);
        $entityModel->addField($fieldModel);

        if (!empty($fieldName)) {
            $this->ensureFieldLocalCacheWarmed($className);
            $this->fieldLocalCache[$className][$fieldName] = $fieldModel;
        }

        return $fieldModel;
    }

    /**
     * Removes all cached data
     */
    public function clearCache()
    {
        $this->entityLocalCache = null;
        $this->fieldLocalCache  = [];
    }

    /**
     * Checks $this->entityLocalCache and if it is empty loads all entity models at once
     */
    protected function ensureEntityLocalCacheWarmed()
    {
        if (null === $this->entityLocalCache) {
            $this->entityLocalCache = [];

            /** @var EntityConfigModel[] $models */
            $models = $this->getEntityManager()
                ->getRepository(EntityConfigModel::ENTITY_NAME)
                ->findAll();
            foreach ($models as $model) {
                $this->entityLocalCache[$model->getClassName()] = $model;
            }
        }
    }

    /**
     * Checks $this->fieldLocalCache[$className] and if it is empty loads all fields models at once
     *
     * @param string $className
     */
    protected function ensureFieldLocalCacheWarmed($className)
    {
        if (!isset($this->fieldLocalCache[$className])) {
            $this->fieldLocalCache[$className] = [];

            $entityModel = $this->findEntityModel($className);
            if ($entityModel) {
                $fields = $entityModel->getFields();
                foreach ($fields as $model) {
                    $this->fieldLocalCache[$className][$model->getFieldName()] = $model;
                }
            }
        }
    }

    /**
     * @param string $className
     * @return EntityConfigModel|null
     */
    protected function loadEntityModel($className)
    {
        $result = $this->getEntityManager()
            ->getRepository(EntityConfigModel::ENTITY_NAME)
            ->findOneBy(['className' => $className]);

        $this->entityLocalCache[$className] = $result;

        return $result;
    }

    /**
     * Determines whether all entities in local cache are detached from an entity manager or not
     */
    protected function areAllEntitiesDetached()
    {
        $result = false;
        if (!empty($this->entityLocalCache)) {
            $result = true;
            foreach ($this->entityLocalCache as $model) {
                if ($model && !$this->isEntityDetached($model)) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Determines whether the given entity is managed by an entity manager or not
     *
     * @param object $entity
     * @return bool
     */
    protected function isEntityDetached($entity)
    {
        $entityState = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityState($entity);

        return $entityState === UnitOfWork::STATE_DETACHED;
    }
}
