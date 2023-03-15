<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;

/**
 * Config extension dumper for entity with enum field
 */
class EnumEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var RelationBuilder */
    protected $relationBuilder;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /** @var ExtendEntityConfigProviderInterface */
    protected $extendEntityConfigProvider;

    public function __construct(
        ConfigManager $configManager,
        RelationBuilder $relationBuilder,
        FieldTypeHelper $fieldTypeHelper,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        ExtendEntityConfigProviderInterface $extendEntityConfigProvider
    ) {
        $this->configManager   = $configManager;
        $this->relationBuilder = $relationBuilder;
        $this->fieldTypeHelper = $fieldTypeHelper;
        $this->nameGenerator   = $nameGenerator;
        $this->extendEntityConfigProvider = $extendEntityConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        return $actionType === ExtendConfigDumper::ACTION_PRE_UPDATE
        || $actionType === ExtendConfigDumper::ACTION_POST_UPDATE;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function preUpdate()
    {
        $enumConfigProvider   = $this->configManager->getProvider('enum');
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityConfigs        = $this->extendEntityConfigProvider->getExtendEntityConfigs();
        foreach ($entityConfigs as $entityConfig) {
            $fieldConfigs = $extendConfigProvider->getConfigs($entityConfig->getId()->getClassName());
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_UPDATE])) {
                    continue;
                }

                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                $fieldType     = $fieldConfigId->getFieldType();
                if (!in_array($fieldType, ['enum', 'multiEnum'])) {
                    continue;
                }

                // prepare input parameters
                $fieldOptions    = [];
                $enumFieldConfig = $enumConfigProvider->getConfig(
                    $fieldConfigId->getClassName(),
                    $fieldConfigId->getFieldName()
                );
                $enumCode        = $enumFieldConfig->get('enum_code');
                $enumName        = $enumFieldConfig->get('enum_name');
                $isPublic        = $enumFieldConfig->get('enum_public');
                if (empty($enumCode) && $isPublic && empty($enumName)) {
                    throw new \LogicException(
                        sprintf(
                            'Both "enum_code" and "enum_name" cannot be empty for a public enum. Field: %s::%s.',
                            $fieldConfigId->getClassName(),
                            $fieldConfigId->getFieldName()
                        )
                    );
                }
                if (empty($enumCode)) {
                    $enumCode = $enumName !== null
                        ? ExtendHelper::buildEnumCode($enumName)
                        : ExtendHelper::generateEnumCode(
                            $fieldConfigId->getClassName(),
                            $fieldConfigId->getFieldName(),
                            $this->nameGenerator->getMaxEnumCodeSize()
                        );

                    $fieldOptions['enum']['enum_code'] = $enumCode;
                }
                $isMultiple = $this->fieldTypeHelper->getUnderlyingType($fieldType) === RelationType::MANY_TO_MANY;
                $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);

                // create an entity is used to store enum values
                $this->createEnumValueConfigEntityModel($enumValueClassName, $enumCode, $isMultiple, $isPublic);

                // create a relation
                if ($isMultiple) {
                    $fieldOptions['extend']['without_default'] = true;
                    $this->relationBuilder->addManyToManyRelation(
                        $entityConfig,
                        $enumValueClassName,
                        $fieldConfigId->getFieldName(),
                        ['name'],
                        ['name'],
                        ['name'],
                        $fieldOptions,
                        $fieldType
                    );
                } else {
                    $this->relationBuilder->addManyToOneRelation(
                        $entityConfig,
                        $enumValueClassName,
                        $fieldConfigId->getFieldName(),
                        'name',
                        $fieldOptions,
                        $fieldType
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     * @throws \ReflectionException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function postUpdate()
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityConfigs        = $this->extendEntityConfigProvider->getExtendEntityConfigs();
        foreach ($entityConfigs as $entityConfig) {
            $entityClassName = $entityConfig->getId()->getClassName();
            if ($entityConfig->is('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS)) {
                $schema          = $entityConfig->get('schema', false, []);
                if (!empty($schema['doctrine'][$entityClassName]['repositoryClass'])) {
                    continue;
                }

                $schema['doctrine'][$entityClassName]['repositoryClass']                =
                    'Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository';
                $schema['doctrine'][$entityClassName]['gedmo']['translation']['entity'] =
                    'Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation';
                $entityConfig->set('schema', $schema);
                $this->configManager->persist($entityConfig);
            } elseif ($entityConfig->is('is_extend')) {
                $fieldConfigs = $extendConfigProvider->getConfigs($entityConfig->getId()->getClassName());
                $reflectionEntityClass = class_exists($entityClassName)
                    ? new EntityReflectionClass($entityClassName)
                    : null;
                foreach ($fieldConfigs as $fieldConfig) {
                    /** @var FieldConfigId $fieldConfigId */
                    $fieldConfigId = $fieldConfig->getId();
                    if ($fieldConfigId->getFieldType() !== 'multiEnum') {
                        continue;
                    }

                    if ($fieldConfig->get('state') === ExtendScope::STATE_DELETE
                        && $reflectionEntityClass
                        && !$reflectionEntityClass->hasProperty($fieldConfigId->getFieldName())) {
                        continue;
                    }

                    $mappingClassName  = $entityConfig->getId()->getClassName();
                    $fieldName         = $fieldConfigId->getFieldName();
                    $snapshotFieldName = ExtendHelper::getMultiEnumSnapshotFieldName($fieldName);

                    $schema = $entityConfig->get('schema', false, []);

                    if (!empty($schema['doctrine'][$mappingClassName]['fields'][$snapshotFieldName])) {
                        continue;
                    }

                    $schema['property'][$snapshotFieldName] = [];
                    if ($fieldConfig->is('is_deleted')) {
                        $schema['property'][$snapshotFieldName]['private'] = true;
                    }

                    $schema['doctrine'][$mappingClassName]['fields'][$snapshotFieldName] = [
                        'column'   => $this->nameGenerator->generateMultiEnumSnapshotColumnName($fieldName),
                        'type'     => 'string',
                        'nullable' => true,
                        'length'   => ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH
                    ];

                    $entityConfig->set('schema', $schema);

                    $this->configManager->persist($entityConfig);
                }
            }
        }
    }

    /**
     * @param string    $enumValueClassName The full class name of an entity is used to store enum values
     * @param string    $enumCode           The unique identifier of an enum
     * @param bool      $isMultiple         Indicates whether several options can be selected for this enum
     *                                      or it supports only one selected option
     * @param bool|null $isPublic Indicates whether this enum can be used by any entity or
     *                                      it is designed to use in one entity only
     *                                      NULL means unspecified. In this case this attribute will not be
     *                                      changed for existing enum entity and will be set to FALSE
     *                                      for new enum entity
     */
    protected function createEnumValueConfigEntityModel($enumValueClassName, $enumCode, $isMultiple, $isPublic)
    {
        if ($this->configManager->hasConfigEntityModel($enumValueClassName)) {
            if (null !== $isPublic) {
                $this->relationBuilder->updateEntityConfigs(
                    $enumValueClassName,
                    [
                        'enum' => [
                            'public' => $isPublic
                        ]
                    ]
                );
            }

            return;
        }

        if (null === $isPublic) {
            $isPublic = false;
        }

        // create entity
        $this->configManager->createConfigEntityModel($enumValueClassName, ConfigModel::MODE_HIDDEN);
        $this->relationBuilder->updateEntityConfigs(
            $enumValueClassName,
            [
                'entity' => [
                    'label'        => ExtendHelper::getEnumTranslationKey('label', $enumCode),
                    'plural_label' => ExtendHelper::getEnumTranslationKey('plural_label', $enumCode),
                    'description'  => ExtendHelper::getEnumTranslationKey('description', $enumCode)
                ],
                'extend' => [
                    'owner'     => ExtendScope::OWNER_SYSTEM,
                    'is_extend' => true,
                    'table'     => $this->nameGenerator->generateEnumTableName($enumCode, true),
                    'inherit'   => ExtendHelper::BASE_ENUM_VALUE_CLASS
                ],
                'enum'   => [
                    'code'     => $enumCode,
                    'public'   => $isPublic,
                    'multiple' => $isMultiple
                ],
            ]
        );
        // create fields
        $this->configManager->createConfigFieldModel($enumValueClassName, 'id', 'string');
        $this->relationBuilder->updateFieldConfigs(
            $enumValueClassName,
            'id',
            [
                'entity' => [
                    'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'id'),
                    'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'id')
                ],
                'importexport' => ['identity' => false]
            ]
        );
        $this->configManager->createConfigFieldModel($enumValueClassName, 'name', 'string');
        $this->relationBuilder->updateFieldConfigs(
            $enumValueClassName,
            'name',
            [
                'entity' => [
                    'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'name'),
                    'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'name')
                ],
                'datagrid'     => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'importexport' => ['identity' => true],
            ]
        );
        $this->configManager->createConfigFieldModel($enumValueClassName, 'priority', 'integer');
        $this->relationBuilder->updateFieldConfigs(
            $enumValueClassName,
            'priority',
            [
                'entity' => [
                    'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'priority'),
                    'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'priority')
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE]
            ]
        );
        $this->configManager->createConfigFieldModel($enumValueClassName, 'default', 'boolean');
        $this->relationBuilder->updateFieldConfigs(
            $enumValueClassName,
            'default',
            [
                'entity' => [
                    'label'       => ExtendHelper::getEnumTranslationKey('label', $enumCode, 'default'),
                    'description' => ExtendHelper::getEnumTranslationKey('description', $enumCode, 'default')
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE]
            ]
        );
    }
}
