<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
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

    /** @var EntityGenerator */
    protected $entityGenerator;

    /** @var array */
    protected $extensions = [];

    /** @var AbstractEntityConfigDumperExtension[]|null */
    protected $sortedExtensions = null;

    /**
     * @param OroEntityManager                $em
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param string                          $cacheDir
     * @param EntityGenerator                 $entityGenerator
     */
    public function __construct(
        OroEntityManager $em,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        EntityGenerator $entityGenerator,
        $cacheDir
    ) {
        $this->nameGenerator   = $nameGenerator;
        $this->em              = $em;
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
     * @param null $className
     */
    public function updateConfig($className = null)
    {
        $aliases = ExtendClassLoadingUtils::getAliases($this->cacheDir);
        $this->clear(true);

        $extendProvider = $this->em->getExtendConfigProvider();

        $extendConfigs = $className
            ? [$extendProvider->getConfig($className)]
            : $extendProvider->getConfigs();

        foreach ($this->getExtensions() as $extension) {
            if ($extension->supports(self::ACTION_PRE_UPDATE)) {
                $extension->preUpdate($extendConfigs);
            }
        }

        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->is('upgradeable')) {
                if ($extendConfig->is('is_extend')) {
                    $this->checkSchema($extendConfig, $aliases);
                }
                // some bundles can change configs in pre persist events,
                // and other bundles can produce more changes depending on already made, it's a bit hacky,
                // but it's a service operation so called inevitable evil
                $extendProvider->flush();
                $this->checkState($extendConfig);
                $extendProvider->flush();
            }
        }

        foreach ($this->getExtensions() as $extension) {
            if ($extension->supports(self::ACTION_POST_UPDATE)) {
                $extension->postUpdate($extendConfigs);
            }
        }

        $this->clear();
    }

    public function dump()
    {
        $schemas        = [];
        $extendProvider = $this->em->getExtendConfigProvider();
        $extendConfigs  = $extendProvider->getConfigs();
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
        if (empty($this->sortedExtensions)) {
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
        if ($fieldConfig->is('extend')) {
            $fieldName = $fieldConfig->getId()->getFieldName();

            // TODO: getting a field type from a model here is a temporary solution.
            // We need to use $fieldType = $fieldConfig->getId()->getFieldType();
            $fieldType = $this->em->getExtendConfigProvider()
                ->getConfigManager()
                ->getConfigFieldModel(
                    $fieldConfig->getId()->getClassName(),
                    $fieldConfig->getId()->getFieldName()
                )
                ->getType();

            if (in_array($fieldType, ['oneToMany', 'manyToOne', 'manyToMany', 'optionSet'])) {
                $relationProperties[$fieldName] = $fieldConfig->getId()->getFieldName();
                if ($fieldType != 'manyToOne' && !$fieldConfig->is('without_default')) {
                    $defaultName = self::DEFAULT_PREFIX . $fieldConfig->getId()->getFieldName();

                    $defaultProperties[$defaultName] = $defaultName;
                }
            } else {
                $properties[$fieldName] = $fieldConfig->getId()->getFieldName();

                $doctrine[$entityName]['fields'][$fieldName]['code']      = $fieldName;
                $doctrine[$entityName]['fields'][$fieldName]['type']      = $fieldType;
                $doctrine[$entityName]['fields'][$fieldName]['nullable']  = true;
                $doctrine[$entityName]['fields'][$fieldName]['length']    = $fieldConfig->get('length');
                $doctrine[$entityName]['fields'][$fieldName]['precision'] = $fieldConfig->get('precision');
                $doctrine[$entityName]['fields'][$fieldName]['scale']     = $fieldConfig->get('scale');
            }
        }

        if (!$fieldConfig->is('state', ExtendScope::STATE_DELETED)) {
            $fieldConfig->set('state', ExtendScope::STATE_ACTIVE);
        }

        if ($fieldConfig->is('state', ExtendScope::STATE_DELETED)) {
            $fieldConfig->set('is_deleted', true);
        }
    }

    /**
     * @param ConfigInterface $extendConfig
     * @param array|null      $aliases
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
                'type'   => 'entity',
                'table'  => $tableName,
                'fields' => [
                    'id' => ['type' => 'integer', 'id' => true, 'generator' => ['strategy' => 'AUTO']]
                ],
            ];
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
                        $relation['field_id']->getFieldType() == 'manyToMany';
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
        }

        $extendConfig->set('schema', $schema);

        $extendProvider->persist($extendConfig);
    }

    /**
     * @param ConfigInterface $extendConfig
     */
    protected function checkState(ConfigInterface $extendConfig)
    {
        $extendProvider = $this->em->getExtendConfigProvider();
        $className      = $extendConfig->getId()->getClassName();
        if ($extendConfig->is('state', ExtendScope::STATE_DELETED)) {
            // mark entity as deleted
            $extendConfig->set('is_deleted', true);
            $extendProvider->persist($extendConfig);

            // mark all fields as deleted
            $extendProvider->map(
                function (Config $config) use ($extendProvider) {
                    if (!$config->is('is_deleted')) {
                        $config->set('is_deleted', true);
                        $extendProvider->persist($config);
                    }
                },
                $className
            );
        } else {
            $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
            $extendProvider->persist($extendConfig);
        }
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

    /**
     * Get entity identifier name by class name
     *
     * @param string $className
     *
     * @return string
     */
    protected function getEntityIdentifier($className)
    {
        // Extend entity always have "id" identifier
        if (ExtendHelper::isCustomEntity($className)) {
            return 'id';
        }

        return $this->em->getClassMetadata($className)->getSingleIdentifierColumnName();
    }
}
