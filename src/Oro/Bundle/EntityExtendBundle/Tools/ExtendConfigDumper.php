<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Mapping\ExtendClassMetadataFactory;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;

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

    /** @var OroEntityManager */
    protected $em;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var EntityGenerator */
    protected $entityGenerator;

    /** @var array */
    protected $extensions = [];

    /** @var AbstractEntityConfigDumperExtension[]|null */
    protected $sortedExtensions = null;

    /**
     * @param OroEntityManager                $em
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param FieldTypeHelper                 $fieldTypeHelper
     * @param string                          $cacheDir
     * @param EntityGenerator                 $entityGenerator
     */
    public function __construct(
        OroEntityManager $em,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        FieldTypeHelper $fieldTypeHelper,
        EntityGenerator $entityGenerator,
        $cacheDir
    ) {
        $this->em              = $em;
        $this->nameGenerator   = $nameGenerator;
        $this->fieldTypeHelper = $fieldTypeHelper;
        $this->entityGenerator = $entityGenerator;
        $this->cacheDir        = $cacheDir;
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

        $extendProvider = $this->em->getExtendConfigProvider();
        $extendConfigs  = $extendProvider->filter($this->createOriginFilterCallback($originsToSkip), null, true);
        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->is('upgradeable')) {
                if ($extendConfig->is('is_extend')) {
                    $this->checkSchema($extendConfig, $aliases, $originsToSkip);
                }

                // some bundles can change configs in pre persist events,
                // and other bundles can produce more changes depending on already made, it's a bit hacky,
                // but it's a service operation so called inevitable evil
                $extendProvider->flush();

                $this->updateStateValues($extendConfig);
            }
        }

        foreach ($extensions as $extension) {
            if ($extension->supports(self::ACTION_POST_UPDATE)) {
                $extension->postUpdate();
            }
        }
        // do one more flush to make sure changes made by post update extensions are saved
        $extendProvider->flush();

        $this->clear();
    }

    public function dump()
    {
        $schemas        = [];
        $extendProvider = $this->em->getExtendConfigProvider();
        $extendConfigs  = $extendProvider->getConfigs(null, true);
        foreach ($extendConfigs as $extendConfig) {
            $schema    = $extendConfig->get('schema');
            $className = $extendConfig->getId()->getClassName();

            if ($schema) {
                $schemas[$className]                 = $schema;
                $schemas[$className]['relationData'] = $extendConfig->get('relation');
            }
        }

        $this->entityGenerator->generate($schemas);
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

        /** @var ExtendClassMetadataFactory $metadataFactory */
        $metadataFactory = $this->em->getMetadataFactory();
        $metadataFactory->clearCache();
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
            if (in_array($underlyingFieldType, ['oneToMany', 'manyToOne', 'manyToMany', 'optionSet'])) {
                $relationProperties[$fieldName] = $fieldName;
                if ($underlyingFieldType != 'manyToOne' && !$fieldConfig->is('without_default')) {
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
        $extendProvider = $this->em->getExtendConfigProvider();
        $className      = $extendConfig->getId()->getClassName();
        $doctrine       = [];
        $entityName     = $className;

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

        $fieldConfigs = $extendProvider->filter($this->createOriginFilterCallback($skippedOrigins), $className, true);
        foreach ($fieldConfigs as $fieldConfig) {
            $this->checkFields(
                $entityName,
                $fieldConfig,
                $relationProperties,
                $defaultProperties,
                $properties,
                $doctrine
            );

            $extendProvider->persist($fieldConfig);
        }

        $relations = $extendConfig->get('relation', false, []);
        foreach ($relations as &$relation) {
            if (!$relation['field_id']) {
                continue;
            }

            $relation['assign'] = true;
            if ($relation['field_id']->getFieldType() != 'manyToOne') {
                $fieldName = $relation['field_id']->getFieldName();

                $addRemoveMethods[$fieldName]['self'] = $fieldName;
                if ($relation['target_field_id']) {
                    $addRemoveMethods[$fieldName]['target']              =
                        $relation['target_field_id']->getFieldName();
                    $addRemoveMethods[$fieldName]['is_target_addremove'] =
                        $relation['field_id']->getFieldType() === 'manyToMany';
                }
            }

            $this->updateRelationValues($relation['target_entity'], $relation['field_id']);
        }
        $extendConfig->set('relation', $relations);

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

        if ($type == 'Extend') {
            $parentClassName = get_parent_class($className);
            if ($parentClassName == $entityName) {
                $parentClassName = $aliases[$entityName];
            }
            $schema['parent']  = $parentClassName;
            $schema['inherit'] = get_parent_class($parentClassName);
        } elseif ($extendConfig->has('inherit')) {
            $schema['inherit'] = $extendConfig->get('inherit');
        }

        $extendConfig->set('schema', $schema);

        $extendProvider->persist($extendConfig);
    }

    /**
     * @param ConfigInterface $extendConfig
     *
     * @return bool
     */
    protected function updateStateValues(ConfigInterface $extendConfig)
    {
        $hasChanges     = false;
        $extendProvider = $this->em->getExtendConfigProvider();
        $className      = $extendConfig->getId()->getClassName();
        $fieldConfigs   = $extendProvider->getConfigs($className, true);

        if ($extendConfig->is('state', ExtendScope::STATE_DELETE)) {
            // mark entity as deleted
            if (!$extendConfig->is('is_deleted')) {
                $extendConfig->set('is_deleted', true);
                $extendProvider->persist($extendConfig);
                $hasChanges = true;
            }

            // mark all fields as deleted
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->is('is_deleted')) {
                    $fieldConfig->set('is_deleted', true);
                    $extendProvider->persist($fieldConfig);
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
                $extendProvider->persist($extendConfig);
            }

            $hasChanges = true;
        }

        if ($hasChanges) {
            $extendProvider->flush();
        }
    }

    /**
     * @param string        $targetClass
     * @param FieldConfigId $fieldId
     */
    protected function updateRelationValues($targetClass, FieldConfigId $fieldId)
    {
        $extendProvider = $this->em->getExtendConfigProvider();
        $targetConfig   = $extendProvider->getConfig($targetClass);

        $relations = $targetConfig->get('relation', false, []);
        $schema    = $targetConfig->get('schema', false, []);

        foreach ($relations as &$relation) {
            if ($relation['target_field_id'] == $fieldId) {
                if ($relation['owner']) {
                    $relation['assign'] = true;
                }

                /** @var FieldConfigId $relationFieldId */
                $relationFieldId = $relation['field_id'];
                if ($relationFieldId) {
                    $schema['relation'][$relationFieldId->getFieldName()] = $relationFieldId->getFieldName();
                }
            }
        }

        $targetConfig->set('relation', $relations);
        $targetConfig->set('schema', $schema);

        $extendProvider->persist($targetConfig);
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
            return !in_array($config->get('origin'), $skippedOrigins, true);
        };
    }
}
