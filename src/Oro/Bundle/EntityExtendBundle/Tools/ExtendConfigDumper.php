<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Dumper for extended entity configs
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExtendConfigDumper
{
    public const ACTION_PRE_UPDATE  = 'preUpdate';
    public const ACTION_POST_UPDATE = 'postUpdate';
    public const DEFAULT_PREFIX     = 'default_';

    /** @var string */
    private $cacheDir;

    /** @var EntityManagerBag */
    private $entityManagerBag;

    /** @var ConfigManager */
    private $configManager;

    /** @var ExtendDbIdentifierNameGenerator */
    private $nameGenerator;

    /** @var FieldTypeHelper */
    private $fieldTypeHelper;

    /** @var EntityGenerator */
    private $entityGenerator;

    /** @var ExtendEntityConfigProviderInterface */
    private $extendEntityConfigProvider;

    /** @var iterable|AbstractEntityConfigDumperExtension[] */
    private $extensions;

    /**
     * @param EntityManagerBag $entityManagerBag
     * @param ConfigManager $configManager
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param FieldTypeHelper $fieldTypeHelper
     * @param EntityGenerator $entityGenerator
     * @param ExtendEntityConfigProviderInterface $extendEntityConfigProvider
     * @param string $cacheDir
     * @param iterable|AbstractEntityConfigDumperExtension[] $extensions
     */
    public function __construct(
        EntityManagerBag $entityManagerBag,
        ConfigManager $configManager,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        FieldTypeHelper $fieldTypeHelper,
        EntityGenerator $entityGenerator,
        ExtendEntityConfigProviderInterface $extendEntityConfigProvider,
        string $cacheDir,
        iterable $extensions
    ) {
        $this->entityManagerBag = $entityManagerBag;
        $this->configManager = $configManager;
        $this->nameGenerator = $nameGenerator;
        $this->fieldTypeHelper = $fieldTypeHelper;
        $this->entityGenerator = $entityGenerator;
        $this->extendEntityConfigProvider = $extendEntityConfigProvider;
        $this->cacheDir = $cacheDir;
        $this->extensions = $extensions;
    }

    /**
     * Gets the cache directory
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Sets the cache directory
     *
     * @param string $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * Update config.
     *
     * @param callable|null $filter function (ConfigInterface $config) : bool
     * @param bool $updateCustom
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @throws \ReflectionException
     */
    public function updateConfig($filter = null, $updateCustom = false)
    {
        $this->clear(true);

        if ($updateCustom) {
            $this->updatePendingConfigs();
        }

        foreach ($this->extensions as $extension) {
            if ($extension->supports(self::ACTION_PRE_UPDATE)) {
                $extension->preUpdate();
            }
        }

        $configProvider = $this->configManager->getProvider('extend');
        $extendConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs($filter);

        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->is('upgradeable')) {
                $this->checkSchema($extendConfig, $configProvider, $filter);
                $this->updateStateValues($extendConfig, $configProvider);
            }
        }

        foreach ($this->extensions as $extension) {
            if ($extension->supports(self::ACTION_POST_UPDATE)) {
                $extension->postUpdate();
            }
        }

        $this->configManager->flush();

        $this->clear();
    }

    public function dump()
    {
        $schemas       = [];
        $extendConfigs = $this->configManager->getProvider('extend')->getConfigs(null, true);
        foreach ($extendConfigs as $extendConfig) {
            $schema    = $extendConfig->get('schema');
            $className = $extendConfig->getId()->getClassName();
            if (!str_contains($className, ExtendClassLoadingUtils::getEntityNamespace())) {
                continue;
            }

            if ($schema) {
                $schemas[$className]                 = $schema;
                $schemas[$className]['relationData'] = $this->getRelationDataForEntity($extendConfigs, $extendConfig);
            }
        }

        $cacheDir = $this->entityGenerator->getCacheDir();
        if ($cacheDir === $this->cacheDir) {
            $this->entityGenerator->generate($schemas);
        } else {
            $this->entityGenerator->setCacheDir($this->cacheDir);
            try {
                $this->entityGenerator->generate($schemas);
                $this->entityGenerator->setCacheDir($cacheDir);
            } catch (\Exception $e) {
                $this->entityGenerator->setCacheDir($cacheDir);
                throw $e;
            }
        }
    }

    /**
     * Load relation data and add state of scope extend  of entity config
     *
     * @param ConfigInterface[] $extendConfigs
     * @param ConfigInterface $entityExtendConfig
     *
     * @return mixed|null
     */
    private function getRelationDataForEntity($extendConfigs, ConfigInterface $entityExtendConfig)
    {
        $relationData = $entityExtendConfig->get('relation', false, []);

        if (is_array($relationData)) {
            foreach ($relationData as $key => &$item) {
                /** @var ConfigInterface $extendConfig */
                foreach ($extendConfigs as $extendConfig) {
                    if ($extendConfig->getId()->getClassName() === $item['target_entity']) {
                        $values = $extendConfig->getValues();
                        $item['state'] = null;
                        if (isset($values['state'])) {
                            $item['state'] = $extendConfig->getValues();
                        }

                        break;
                    }
                }
            }
        }

        return $relationData;
    }

    /**
     * Makes sure that extended entity configs are ready to be processing by other config related commands
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function checkConfig()
    {
        $hasClasses = ExtendClassLoadingUtils::classesExist($this->cacheDir);
        if ($hasClasses) {
            return;
        }

        $hasChanges    = false;
        $extendConfigs = $this->configManager->getProvider('extend')->getConfigs(null, true);
        foreach ($extendConfigs as $extendConfig) {
            $schema = $extendConfig->get('schema', false, []);

            // make sure that inheritance definition for extended entities is up-to-date
            // this check is required to avoid doctrine mapping exceptions during the platform update
            // in case if a parent class is changed in a new version of a code
            if (isset($schema['type'], $schema['class'], $schema['entity']) && $schema['type'] === 'Extend') {
                $entityClassName = $schema['entity'];
                if (!class_exists($schema['class'])) {
                    throw new \InvalidArgumentException(sprintf('Unknown class "%s".', $schema['class']));
                }
                $parentClassName = get_parent_class($schema['class']);
                if (false !== $parentClassName && $parentClassName !== $entityClassName) {
                    $inheritClassName = get_parent_class($parentClassName);

                    $hasSchemaChanges = false;
                    if (!isset($schema['parent']) || $schema['parent'] !== $parentClassName) {
                        $hasSchemaChanges = true;
                        $schema['parent'] = $parentClassName;
                    }
                    if (!isset($schema['inherit']) || $schema['inherit'] !== $inheritClassName) {
                        $hasSchemaChanges  = true;
                        $schema['inherit'] = $inheritClassName;
                    }

                    if ($hasSchemaChanges) {
                        $hasChanges = true;
                        $extendConfig->set('schema', $schema);
                        $this->configManager->persist($extendConfig);
                    }
                }
            }
        }

        if ($hasChanges) {
            $this->configManager->flush();
        }
    }

    /**
     * Removes the entity proxies and metadata from the cache
     *
     * @param bool $keepEntityProxies Set TRUE if proxies for custom and extend entities should not be deleted
     */
    public function clear($keepEntityProxies = false)
    {
        $filesystem = new Filesystem();

        if (!$keepEntityProxies) {
            $baseCacheDir = ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir);
            if ($filesystem->exists($baseCacheDir)) {
                $finder = new Finder();
                $finder->files()->in($baseCacheDir);
                $filesystem->remove($finder);
            }
        }

        foreach ($this->entityManagerBag->getEntityManagers() as $em) {
            /** @var ClassMetadataFactory $metadataFactory */
            $metadataFactory = $em->getMetadataFactory();
            if (is_callable([$metadataFactory, 'clearCache'])) {
                $metadataFactory->clearCache();
            }
        }
    }

    /**
     * Check fields parameters and update field config
     *
     * @param string          $entityName
     * @param ConfigInterface $fieldConfig
     * @param array           $relationProperties
     * @param array           $defaultProperties
     * @param array           $properties
     * @param array           $doctrine
     * @param \ReflectionClass|null $reflectionEntityClass
     * @throws \ReflectionException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function checkFieldSchema(
        $entityName,
        ConfigInterface $fieldConfig,
        array &$relationProperties,
        array &$defaultProperties,
        array &$properties,
        array &$doctrine,
        ?\ReflectionClass $reflectionEntityClass
    ) {
        if ($fieldConfig->is('is_extend')) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $fieldConfig->getId();
            if ($fieldConfig->get('state') === ExtendScope::STATE_DELETE &&
                $reflectionEntityClass &&
                !$reflectionEntityClass->hasProperty($fieldConfigId->getFieldName())) {
                return;
            }
            $fieldName     = $fieldConfigId->getFieldName();
            $fieldType     = $fieldConfigId->getFieldType();
            $isDeleted     = $fieldConfig->is('is_deleted');
            $columnName    = $fieldConfig->get('column_name', false, $fieldName);

            $underlyingFieldType = $this->fieldTypeHelper->getUnderlyingType($fieldType);
            if (in_array($underlyingFieldType, RelationType::$anyToAnyRelations, true)) {
                $relationProperties[$fieldName] = [];
                if ($isDeleted) {
                    $relationProperties[$fieldName]['private'] = true;
                }
                if ($underlyingFieldType !== RelationType::MANY_TO_ONE && !$fieldConfig->is('without_default')) {
                    $defaultName = self::DEFAULT_PREFIX . $fieldName;

                    $defaultProperties[$defaultName] = [];
                    if ($isDeleted) {
                        $defaultProperties[$defaultName]['private'] = true;
                    }
                }
            } else {
                $properties[$fieldName] = [];
                if ($isDeleted) {
                    $properties[$fieldName]['private'] = true;
                }

                $doctrine[$entityName]['fields'][$fieldName] = [
                    'column'    => $columnName,
                    'type'      => $fieldType,
                    'nullable'  => $fieldConfig->get('nullable', false, true),
                    'length'    => $fieldConfig->get('length'),
                    'precision' => $fieldConfig->get('precision'),
                    'scale'     => $fieldConfig->get('scale'),
                    'default'   => $fieldConfig->get('default'),
                ];
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @param ConfigInterface $extendConfig
     * @param ConfigProvider  $configProvider
     * @param callable|null   $filter function (ConfigInterface $config) : bool
     * @throws \ReflectionException
     */
    private function checkSchema(
        ConfigInterface $extendConfig,
        ConfigProvider $configProvider,
        $filter = null
    ) {
        $className  = $extendConfig->getId()->getClassName();
        $doctrine   = [];
        $entityName = $className;

        if (ExtendHelper::isCustomEntity($className)) {
            $type      = 'Custom';
            $tableName = $extendConfig->get('table');
            if (!$tableName) {
                $tableName = $this->nameGenerator->generateCustomEntityTableName($className);
            }
            $doctrine[$entityName] = [
                'type'  => 'entity',
                'table' => $tableName
            ];
            // add 'id' field only for Custom entity without inheritance
            if (!$extendConfig->has('inherit')) {
                $doctrine[$entityName]['fields'] = [
                    'id' => ['type' => 'integer', 'id' => true, 'generator' => ['strategy' => 'AUTO']]
                ];
            }
        } else {
            $type = 'Extend';
            $entityName = $className;
            $doctrine[$entityName] = [
                'type'   => 'entity',
                'fields' => [],
            ];
        }

        $schema             = $extendConfig->get('schema', false, []);
        $properties         = isset($schema['property']) && null !== $filter ? $schema['property'] : [];
        $relationProperties = isset($schema['relation']) && null !== $filter ? $schema['relation'] : [];
        $defaultProperties  = isset($schema['default']) && null !== $filter ? $schema['default'] : [];
        $addRemoveMethods   = isset($schema['addremove']) && null !== $filter ? $schema['addremove'] : [];
        $attribute          = $schema['attribute'] ?? null;

        $fieldConfigs = null === $filter
            ? $configProvider->getConfigs($className, true)
            : $configProvider->filter($filter, $className, true);
        $reflectionEntityClass = class_exists($entityName)
            ? new EntityReflectionClass($entityName)
            : null;
        foreach ($fieldConfigs as $fieldConfig) {
            $this->updateFieldState($fieldConfig);
            $this->checkFieldSchema(
                $entityName,
                $fieldConfig,
                $relationProperties,
                $defaultProperties,
                $properties,
                $doctrine,
                $reflectionEntityClass
            );
        }

        $relations = $extendConfig->get('relation', false, []);
        foreach ($relations as $relation) {
            /** @var FieldConfigId $fieldId */
            $fieldId = $relation['field_id'];
            if (!$fieldId) {
                continue;
            }

            $fieldName = $fieldId->getFieldName();
            $fieldConfig = $configProvider->hasConfig($fieldId->getClassName(), $fieldName)
                ? $configProvider->getConfig($fieldId->getClassName(), $fieldName)
                : null;
            $isDeleted = $fieldConfig ? $fieldConfig->is('is_deleted') : false;
            if (!isset($relationProperties[$fieldName])) {
                $relationProperties[$fieldName] = [];
                if ($fieldConfig && $fieldConfig->get('state') === ExtendScope::STATE_DELETE
                    && $reflectionEntityClass
                    && !$reflectionEntityClass->hasProperty($fieldId->getFieldName())) {
                    continue;
                }
                if ($isDeleted) {
                    $relationProperties[$fieldName]['private'] = true;
                }
            }
            if (!$isDeleted && $fieldId->getFieldType() !== RelationType::MANY_TO_ONE) {
                $addRemoveMethods[$fieldName]['self'] = $fieldName;

                /** @var FieldConfigId $targetFieldId */
                $targetFieldId = $relation['target_field_id'];
                if ($targetFieldId) {
                    $fieldType = $fieldId->getFieldType();

                    $addRemoveMethods[$fieldName]['target']              = $targetFieldId->getFieldName();
                    $addRemoveMethods[$fieldName]['is_target_addremove'] = $fieldType === RelationType::MANY_TO_MANY;
                }
            }
        }

        $schema = [
            'class'     => $className,
            'entity'    => $entityName,
            'type'      => $type,
            'property'  => $properties,
            'relation'  => $relationProperties,
            'default'   => $defaultProperties,
            'addremove' => $addRemoveMethods,
            'doctrine'  => $doctrine,
        ];
        if (null !== $attribute) {
            $schema['attribute'] = $attribute;
        }

        if ($type === 'Extend') {
            $parentClassName = class_exists($className) ? get_parent_class($className) : false;
            $schema['parent']  = $parentClassName;
            $schema['inherit'] = class_exists($parentClassName) ? get_parent_class($parentClassName) : false;
        } elseif ($extendConfig->has('inherit')) {
            $schema['inherit'] = $extendConfig->get('inherit');
        }

        $extendConfig->set('schema', $schema);

        $this->configManager->persist($extendConfig);
    }

    private function updateFieldState(ConfigInterface $fieldConfig)
    {
        if ($fieldConfig->is('state', ExtendScope::STATE_DELETE)) {
            $fieldConfig->set('is_deleted', true);
            $this->configManager->persist($fieldConfig);
        } elseif (!$fieldConfig->is('state', ExtendScope::STATE_ACTIVE)) {
            if ($fieldConfig->is('state', ExtendScope::STATE_RESTORE)) {
                $fieldConfig->set('is_deleted', false);
            }
            $fieldConfig->set('state', ExtendScope::STATE_ACTIVE);
            $this->configManager->persist($fieldConfig);
        }
    }

    private function updateStateValues(ConfigInterface $entityConfig, ConfigProvider $configProvider)
    {
        if ($entityConfig->is('state', ExtendScope::STATE_DELETE)) {
            // mark entity as deleted
            if (!$entityConfig->is('is_deleted')) {
                $entityConfig->set('is_deleted', true);
                $this->configManager->persist($entityConfig);
            }

            // mark all fields as deleted
            $fieldConfigs = $configProvider->getConfigs($entityConfig->getId()->getClassName(), true);
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->is('is_deleted')) {
                    $fieldConfig->set('is_deleted', true);
                    $this->configManager->persist($fieldConfig);
                }
            }
        } elseif (!$entityConfig->is('state', ExtendScope::STATE_ACTIVE)) {
            $hasNotActiveFields = false;

            $fieldConfigs = $configProvider->getConfigs($entityConfig->getId()->getClassName(), true);
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_DELETE])) {
                    $hasNotActiveFields = true;
                    break;
                }
            }

            // Set entity state to active if all fields are active or deleted
            if (!$hasNotActiveFields) {
                $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
                $this->configManager->persist($entityConfig);
            }
        }
    }

    /**
     * Updates pending configs
     */
    private function updatePendingConfigs()
    {
        $pendingChanges = [];

        $configs = $this->configManager->getProvider('extend')->getConfigs();
        foreach ($configs as $config) {
            $configPendingChanges = $config->get('pending_changes');
            if (!$configPendingChanges) {
                continue;
            }

            $pendingChanges[$config->getId()->getClassName()] = $configPendingChanges;
            $config->remove('pending_changes');
            $this->configManager->persist($config);
        }

        foreach ($pendingChanges as $className => $changes) {
            foreach ($changes as $scope => $values) {
                $provider = $this->configManager->getProvider($scope);
                $config = $provider->getConfig($className);
                foreach ($values as $code => $value) {
                    $config->set($code, ExtendHelper::updatedPendingValue($config->get($code), $value));
                }
                $this->configManager->persist($config);
            }
        }
    }

    public function validateExtendEntityConfig(): void
    {
        $extendConfigs = $this->configManager->getProvider('extend')->getConfigs(null, true);
        foreach ($extendConfigs as $extendConfig) {
            $schema = $extendConfig->get('schema');
            $className = $extendConfig->getId()->getClassName();
            if (isset($schema['type']) && 'Extend' === $schema['type'] && !ExtendHelper::isExtendEntity($className)) {
                throw new \LogicException(
                    'Extend class "' . $className . '"should implements ' . ExtendEntityInterface::class
                );
            }
        }
    }
}
