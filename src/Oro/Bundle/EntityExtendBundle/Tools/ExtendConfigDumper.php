<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\Cache\ClearableCache;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExtendConfigDumper
{
    const ACTION_PRE_UPDATE  = 'preUpdate';
    const ACTION_POST_UPDATE = 'postUpdate';

    /** @deprecated Use ExtendHelper::getExtendEntityProxyClassName and ExtendHelper::ENTITY_NAMESPACE instead */
    const ENTITY = 'Extend\\Entity\\';

    const DEFAULT_PREFIX = 'default_';

    /** @var string */
    protected $cacheDir;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var EntityGenerator */
    protected $entityGenerator;

    /** @var array */
    protected $extensions = [];

    /** @var AbstractEntityConfigDumperExtension[]|null */
    protected $sortedExtensions;

    /**
     * @param ConfigProvider                  $configProvider
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param FieldTypeHelper                 $fieldTypeHelper
     * @param EntityGenerator                 $entityGenerator
     * @param string                          $cacheDir
     */
    public function __construct(
        ConfigProvider $configProvider,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        FieldTypeHelper $fieldTypeHelper,
        EntityGenerator $entityGenerator,
        $cacheDir
    ) {
        $this->configProvider  = $configProvider;
        $this->nameGenerator   = $nameGenerator;
        $this->fieldTypeHelper = $fieldTypeHelper;
        $this->entityGenerator = $entityGenerator;
        $this->cacheDir        = $cacheDir;
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
     * @param AbstractEntityConfigDumperExtension $extension
     * @param int                                 $priority
     */
    public function addExtension(AbstractEntityConfigDumperExtension $extension, $priority = 0)
    {
        if (!isset($this->extensions[$priority])) {
            $this->extensions[$priority] = [];
        }

        $this->extensions[$priority][] = $extension;
    }

    /**
     * Update config.
     *
     * @param array $originsToSkip
     */
    public function updateConfig(array $originsToSkip = [])
    {
        $aliases = ExtendClassLoadingUtils::getAliases($this->cacheDir);
        $this->clear(true);

        $extensions = $this->getExtensions();

        foreach ($extensions as $extension) {
            if ($extension->supports(self::ACTION_PRE_UPDATE)) {
                $extension->preUpdate();
            }
        }

        $extendConfigs  = $this->configProvider->filter($this->createOriginFilterCallback($originsToSkip), null, true);
        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->is('upgradeable')) {
                if ($extendConfig->is('is_extend')) {
                    $this->checkSchema($extendConfig, $aliases, $originsToSkip);
                }

                // some bundles can change configs in pre persist events,
                // and other bundles can produce more changes depending on already made, it's a bit hacky,
                // but it's a service operation so called inevitable evil
                $this->configProvider->flush();
                // the clearing of an entity manager gives a performance gain of 4 times
                $this->configProvider->getConfigManager()->getEntityManager()->clear();

                $this->updateStateValues($extendConfig);
            }
        }

        foreach ($extensions as $extension) {
            if ($extension->supports(self::ACTION_POST_UPDATE)) {
                $extension->postUpdate();
            }
        }
        // do one more flush to make sure changes made by post update extensions are saved
        $this->configProvider->flush();

        $this->clear();
    }

    public function dump()
    {
        $schemas        = [];
        $extendConfigs  = $this->configProvider->getConfigs(null, true);
        foreach ($extendConfigs as $extendConfig) {
            $schema    = $extendConfig->get('schema');
            $className = $extendConfig->getId()->getClassName();

            if ($schema) {
                $schemas[$className]                 = $schema;
                $schemas[$className]['relationData'] = $extendConfig->get('relation');
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
     * Removes the entity proxies and metadata from the cache
     *
     * @param bool $keepEntityProxies Set TRUE if proxies for custom and extend entities should not be deleted
     */
    public function clear($keepEntityProxies = false)
    {
        $filesystem = new Filesystem();

        if ($keepEntityProxies) {
            $aliasesPath = ExtendClassLoadingUtils::getAliasesPath($this->cacheDir);
            if ($filesystem->exists($aliasesPath)) {
                $filesystem->remove($aliasesPath);
            }
        } else {
            $baseCacheDir = ExtendClassLoadingUtils::getEntityBaseCacheDir($this->cacheDir);
            if ($filesystem->exists($baseCacheDir)) {
                $filesystem->remove([$baseCacheDir]);
            }
            $filesystem->mkdir(ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir));
        }

        $metadataCacheDriver = $this->configProvider
            ->getConfigManager()
            ->getEntityManager()
            ->getMetadataFactory()
            ->getCacheDriver();
        if ($metadataCacheDriver instanceof ClearableCache) {
            $metadataCacheDriver->deleteAll();
        }
    }

    /**
     * Return sorted extensions
     *
     * @return AbstractEntityConfigDumperExtension[]
     */
    protected function getExtensions()
    {
        if (null === $this->sortedExtensions) {
            krsort($this->extensions);
            $this->sortedExtensions = call_user_func_array('array_merge', $this->extensions);
        }
        return $this->sortedExtensions;
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
     */
    protected function checkFields(
        $entityName,
        ConfigInterface $fieldConfig,
        array &$relationProperties,
        array &$defaultProperties,
        array &$properties,
        array &$doctrine
    ) {
        if ($fieldConfig->is('is_extend')) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $fieldConfig->getId();
            $fieldName     = $fieldConfigId->getFieldName();
            $fieldType     = $fieldConfigId->getFieldType();

            $underlyingFieldType = $this->fieldTypeHelper->getUnderlyingType($fieldType);
            if (in_array($underlyingFieldType, array_merge(RelationType::$anyToAnyRelations, ['optionSet']))) {
                $relationProperties[$fieldName] = $fieldName;
                if ($underlyingFieldType !== RelationType::MANY_TO_ONE && !$fieldConfig->is('without_default')) {
                    $defaultName = self::DEFAULT_PREFIX . $fieldName;

                    $defaultProperties[$defaultName] = $defaultName;
                }
            } else {
                $properties[$fieldName] = $fieldName;

                $doctrine[$entityName]['fields'][$fieldName] = [
                    'column'    => $fieldName,
                    'type'      => $fieldType,
                    'nullable'  => true,
                    'length'    => $fieldConfig->get('length'),
                    'precision' => $fieldConfig->get('precision'),
                    'scale'     => $fieldConfig->get('scale'),
                ];
            }
        }

        if ($fieldConfig->is('state', ExtendScope::STATE_DELETE)) {
            $fieldConfig->set('is_deleted', true);
        } else {
            $fieldConfig->set('state', ExtendScope::STATE_ACTIVE);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @param ConfigInterface $extendConfig
     * @param array|null $aliases
     * @param array|null $skippedOrigins
     */
    protected function checkSchema(ConfigInterface $extendConfig, $aliases, array $skippedOrigins = null)
    {
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
            $type                  = 'Extend';
            $entityName            = $extendConfig->get('extend_class');
            $doctrine[$entityName] = [
                'type'   => 'mappedSuperclass',
                'fields' => [],
            ];
        }

        $schema             = $extendConfig->get('schema');
        $properties         = [];
        $relationProperties = $schema ? $schema['relation'] : [];
        $defaultProperties  = [];
        $addRemoveMethods   = [];

        $fieldConfigs = $this->configProvider->filter(
            $this->createOriginFilterCallback($skippedOrigins),
            $className,
            true
        );
        foreach ($fieldConfigs as $fieldConfig) {
            $this->checkFields(
                $entityName,
                $fieldConfig,
                $relationProperties,
                $defaultProperties,
                $properties,
                $doctrine
            );

            $this->configProvider->persist($fieldConfig);
        }

        $relations = $extendConfig->get('relation', false, []);
        foreach ($relations as $relation) {
            /** @var FieldConfigId $fieldId */
            $fieldId = $relation['field_id'];
            if (!$fieldId) {
                continue;
            }

            $fieldName = $fieldId->getFieldName();
            if (!isset($relationProperties[$fieldName])) {
                $relationProperties[$fieldName] = $fieldName;
            }
            if ($fieldId->getFieldType() !== RelationType::MANY_TO_ONE) {
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

        if ($type === 'Extend') {
            $parentClassName = get_parent_class($className);
            if ($parentClassName === $entityName) {
                $parentClassName = $aliases[$entityName];
            }
            $schema['parent']  = $parentClassName;
            $schema['inherit'] = get_parent_class($parentClassName);
        } elseif ($extendConfig->has('inherit')) {
            $schema['inherit'] = $extendConfig->get('inherit');
        }

        $extendConfig->set('schema', $schema);

        $this->configProvider->persist($extendConfig);
    }

    /**
     * @param ConfigInterface $extendConfig
     *
     * @return bool
     */
    protected function updateStateValues(ConfigInterface $extendConfig)
    {
        $hasChanges   = false;
        $className    = $extendConfig->getId()->getClassName();
        $fieldConfigs = $this->configProvider->getConfigs($className, true);

        if ($extendConfig->is('state', ExtendScope::STATE_DELETE)) {
            // mark entity as deleted
            if (!$extendConfig->is('is_deleted')) {
                $extendConfig->set('is_deleted', true);
                $this->configProvider->persist($extendConfig);
                $hasChanges = true;
            }

            // mark all fields as deleted
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->is('is_deleted')) {
                    $fieldConfig->set('is_deleted', true);
                    $this->configProvider->persist($fieldConfig);
                    $hasChanges = true;
                }
            }
        } elseif (!$extendConfig->is('state', ExtendScope::STATE_ACTIVE)) {
            $hasNotActiveFields = false;
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->is('state', ExtendScope::STATE_DELETE)
                    && !$fieldConfig->is('state', ExtendScope::STATE_ACTIVE)
                ) {
                    $hasNotActiveFields = true;
                    break;
                }
            }

            // Set entity state to active if all fields are active or deleted
            if (!$hasNotActiveFields) {
                $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
                $this->configProvider->persist($extendConfig);
            }

            $hasChanges = true;
        }

        if ($hasChanges) {
            $this->configProvider->flush();
        }
    }

    /**
     * Return callback that could be used for filtering purposes in order to skip config entries that has origin in list
     * of currently skipped
     *
     * @param array $skippedOrigins
     *
     * @return callable
     */
    protected function createOriginFilterCallback(array $skippedOrigins)
    {
        return function (ConfigInterface $config) use ($skippedOrigins) {
            return
                $config->get('state') === ExtendScope::STATE_ACTIVE
                || !in_array($config->get('origin'), $skippedOrigins, true);
        };
    }
}
