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

    const ENTITY         = 'Extend\\Entity\\';
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

    public function updateConfig()
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
        $extendConfigs  = $extendProvider->getConfigs(null, true);
        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->is('upgradeable')) {
                if ($extendConfig->is('is_extend')) {
                    $this->checkSchema($extendConfig, $aliases);
                }

                // some bundles can change configs in pre persist events,
                // and other bundles can produce more changes depending on already made, it's a bit hacky,
                // but it's a service operation so called inevitable evil
                $extendProvider->flush();

                if ($this->checkState($extendConfig)) {
                    $extendProvider->flush();
                }
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

        if (!$fieldConfig->is('state', ExtendScope::STATE_DELETE)) {
            $fieldConfig->set('state', ExtendScope::STATE_ACTIVE);
        }

        if ($fieldConfig->is('state', ExtendScope::STATE_DELETE)) {
            $fieldConfig->set('is_deleted', true);
        }
    }

    /**
     * @param ConfigInterface $extendConfig
     * @param array|null      $aliases
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function checkSchema(ConfigInterface $extendConfig, $aliases)
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

        $fieldConfigs = $extendProvider->getConfigs($className);
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

            $this->checkRelation($relation['target_entity'], $relation['field_id']);
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
    protected function checkState(ConfigInterface $extendConfig)
    {
        $hasChanges = false;
        $extendProvider = $this->em->getExtendConfigProvider();
        $className      = $extendConfig->getId()->getClassName();
        if ($extendConfig->is('state', ExtendScope::STATE_DELETE)) {
            // mark entity as deleted
            if (!$extendConfig->is('is_deleted')) {
                $extendConfig->set('is_deleted', true);
                $extendProvider->persist($extendConfig);
                $hasChanges = true;
            }

            // mark all fields as deleted
            $fieldConfigs = $extendProvider->getConfigs($className, true);
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->is('is_deleted')) {
                    $fieldConfig->set('is_deleted', true);
                    $extendProvider->persist($fieldConfig);
                    $hasChanges = true;
                }
            }
        } elseif (!$extendConfig->is('state', ExtendScope::STATE_ACTIVE)) {
            $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
            $extendProvider->persist($extendConfig);
            $hasChanges = true;
        }

        return $hasChanges;
    }

    /**
     * @param string        $targetClass
     * @param FieldConfigId $fieldId
     */
    protected function checkRelation($targetClass, FieldConfigId $fieldId)
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
                if ($relationFieldId && count($schema)) {
                    $schema['relation'][$relationFieldId->getFieldName()] =
                        $relationFieldId->getFieldName();
                }
            }
        }

        $targetConfig->set('relation', $relations);
        $targetConfig->set('schema', $schema);

        $extendProvider->persist($targetConfig);
    }
}
