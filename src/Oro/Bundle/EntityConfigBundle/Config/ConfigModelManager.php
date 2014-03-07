<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityConfigBundle\Exception\LogicException;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;

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
     * @var AbstractConfigModel[]|ArrayCollection
     */
    protected $localCache;

    /**
     * @var bool
     */
    protected $dbCheckCache;

    /**
     * @var ServiceLink
     */
    protected $proxyEm;

    private $ignoreModel = array(
        'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel',
        'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel',
        'Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel',
    );

    private $requiredTables = array(
        'oro_entity_config',
        'oro_entity_config_field',
        'oro_entity_config_value',
    );

    public function __construct(ServiceLink $proxyEm)
    {
        $this->localCache = new ArrayCollection();
        $this->proxyEm    = $proxyEm;
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
            try {
                $conn = $this->getEntityManager()->getConnection();

                if (!$conn->isConnected()) {
                    $this->getEntityManager()->getConnection()->connect();
                }

                $this->dbCheckCache = $conn->isConnected()
                    && (bool)array_intersect(
                        $this->requiredTables,
                        $this->getEntityManager()->getConnection()->getSchemaManager()->listTableNames()
                    );
            } catch (\PDOException $e) {
                $this->dbCheckCache = false;
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
        if (empty($className) || in_array($className, $this->ignoreModel)) {
            return null;
        }

        $result = false;

        // check if a model exists in the local cache
        $cacheKey = $this->buildEntityLocalCacheKey($className);
        if ($this->localCache->containsKey($cacheKey)) {
            $result = $this->localCache->get($cacheKey);
            if ($result && $this->isEntityDetached($result)) {
                // the detached model must be reloaded
                $result = false;
                $this->removeFromLocalCache($className);
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
        if (empty($className) || empty($fieldName) || in_array($className, $this->ignoreModel)) {
            return null;
        }

        $result = false;

        // check if a model exists in the local cache
        $cacheKey = $this->buildFieldLocalCacheKey($className, $fieldName);
        if ($this->localCache->containsKey($cacheKey)) {
            $result = $this->localCache->get($cacheKey);
            if ($result && $this->isEntityDetached($result)) {
                // the detached model must be reloaded
                $result = false;
                $this->removeFromLocalCache($className);
            }
        }

        // load a model if it was not found in the local cache
        if ($result === false) {
            $result = $this->loadEntityFieldModel($className, $fieldName);
        }

        return $result;
    }

    /**
     * @param string      $className
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
                sprintf('A model for "%s" was not found ', $className)
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
                sprintf('A model for "%s::%s" was not found ', $className, $fieldName)
            );
        }

        return $model;
    }

    /**
     * Changes a type of a field
     * Important: this method do not save changes in a database. To do this you need to call entityManager->flush
     *
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @throws \InvalidArgumentException if $className, $fieldName or $fieldType is empty
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

        $fieldModel = $this->findFieldModel($className, $fieldName);
        if ($fieldModel && $fieldModel->getType() !== $fieldType) {
            $fieldModel->setType($fieldType);
            $this->getEntityManager()->persist($fieldModel);
            $this->localCache->set($this->buildFieldLocalCacheKey($className, $fieldName), $fieldModel);
        }
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
     * @param null $className
     * @return AbstractConfigModel[]
     */
    public function getModels($className = null)
    {
        if ($className) {
            return $this->getEntityModel($className)->getFields()->toArray();
        } else {
            $entityConfigModelRepo = $this->getEntityManager()->getRepository(EntityConfigModel::ENTITY_NAME);

            return (array)$entityConfigModelRepo->findAll();
        }
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
            $this->localCache->set($this->buildEntityLocalCacheKey($className), $entityModel);
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
        if (!in_array($mode, array(self::MODE_DEFAULT, self::MODE_HIDDEN, self::MODE_READONLY))) {
            throw new \InvalidArgumentException(sprintf('Invalid $mode: "%s"', $mode));
        }

        $entityModel = $this->getEntityModel($className);

        $fieldModel = new FieldConfigModel($fieldName, $fieldType);
        $fieldModel->setMode($mode);
        $entityModel->addField($fieldModel);

        if (!empty($fieldName)) {
            $this->localCache->set($this->buildFieldLocalCacheKey($className, $fieldName), $fieldModel);
        }

        return $fieldModel;
    }

    /**
     * Removes all cached data
     */
    public function clearCache()
    {
        $this->localCache->clear();
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
        $this->localCache->set($this->buildEntityLocalCacheKey($className), $result);

        return $result;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return FieldConfigModel|null
     */
    protected function loadEntityFieldModel($className, $fieldName)
    {
        $result                   = null;
        $fieldsLoadedFlagCacheKey = $this->buildFieldLocalCacheKey($className, '!');
        if (!$this->localCache->containsKey($fieldsLoadedFlagCacheKey)) {
            // set a flag indicates that field models are loaded in the local cache
            $this->localCache->set($fieldsLoadedFlagCacheKey, true);
            // load models for all fields and put them in the local cache
            $entityModel = $this->findEntityModel($className);
            if ($entityModel) {
                foreach ($entityModel->getFields() as $fieldModel) {
                    $this->localCache->set(
                        $this->buildFieldLocalCacheKey($className, $fieldModel->getFieldName()),
                        $fieldModel
                    );
                }
                // get a field model from the local cache
                $result = $this->localCache->get($this->buildFieldLocalCacheKey($className, $fieldName));
            }
        }

        return $result;
    }

    /**
     * Removes the given entity model and its fields from the local cache
     *
     * @param string $className
     */
    protected function removeFromLocalCache($className)
    {
        $toBeRemovedKeys = array_filter(
            $this->localCache->getKeys(),
            function ($key) use ($className) {
                return strpos($key, $className) === 0;
            }
        );
        foreach ($toBeRemovedKeys as $key) {
            $this->localCache->remove($key);
        }
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

    /**
     * Returns a string unique identifies each config model
     *
     * @param string $className
     * @return string
     * @throws LogicException if $className is empty
     */
    protected function buildEntityLocalCacheKey($className)
    {
        if (empty($className)) {
            throw new LogicException('$className must not be empty');
        }

        return $className;
    }

    /**
     * Returns a string unique identifies each config model
     *
     * @param string $className
     * @param string $fieldName
     * @return string
     * @throws LogicException if $className or $fieldName is empty
     */
    protected function buildFieldLocalCacheKey($className, $fieldName)
    {
        if (empty($className)) {
            throw new LogicException('$className must not be empty');
        }
        if (empty($fieldName)) {
            throw new LogicException('$fieldName must not be empty');
        }

        return sprintf('%s::%s', $className, $fieldName);
    }
}
