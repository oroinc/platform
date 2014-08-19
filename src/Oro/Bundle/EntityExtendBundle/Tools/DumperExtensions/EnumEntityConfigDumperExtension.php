<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;

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

    /**
     * @param ConfigManager                   $configManager
     * @param RelationBuilder                 $relationBuilder
     * @param FieldTypeHelper                 $fieldTypeHelper
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(
        ConfigManager $configManager,
        RelationBuilder $relationBuilder,
        FieldTypeHelper $fieldTypeHelper,
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->configManager   = $configManager;
        $this->relationBuilder = $relationBuilder;
        $this->fieldTypeHelper = $fieldTypeHelper;
        $this->nameGenerator   = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        return $actionType === ExtendConfigDumper::ACTION_PRE_UPDATE;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityConfigs        = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('is_extend')) {
                continue;
            }

            $fieldConfigs = $extendConfigProvider->getConfigs($entityConfig->getId()->getClassName());
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->is('state', ExtendScope::STATE_NEW)) {
                    continue;
                }

                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                $fieldType     = $fieldConfigId->getFieldType();
                if (in_array($fieldType, ['enum', 'multiEnum'])) {
                    $fieldName          = $fieldConfigId->getFieldName();
                    $enumConfigProvider = $this->configManager->getProvider('enum');
                    $enumFieldConfig    = $enumConfigProvider->getConfig($fieldConfigId->getClassName(), $fieldName);
                    $enumName           = $enumFieldConfig->get('enum_name');
                    if (!empty($enumName)) {
                        $fieldOptions = [
                            'importexport' => [
                                'process_as_scalar' => true
                            ]
                        ];
                        $enumCode     = $enumFieldConfig->get('enum_code');
                        if (empty($enumCode)) {
                            $enumCode                          = ExtendHelper::buildEnumCode($enumName);
                            $fieldOptions['enum']['enum_code'] = $enumCode;
                        }
                        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
                        $underlyingType     = $this->fieldTypeHelper->getUnderlyingType($fieldType);
                        $isMultiple         = $underlyingType === 'manyToMany';
                        $isPublic           = $enumFieldConfig->get('enum_public');
                        // create an entity is used to store enum values
                        $this->createEnumValueConfigEntityModel(
                            $enumValueClassName,
                            $enumCode,
                            $isMultiple,
                            $isPublic
                        );
                        // create a relation
                        if ($isMultiple) {
                            $fieldOptions['extend']['without_default'] = true;
                            $this->relationBuilder->addManyToManyRelation(
                                $entityConfig,
                                $enumValueClassName,
                                $fieldName,
                                ['id'],
                                ['id'],
                                ['id'],
                                $fieldOptions,
                                $fieldType
                            );
                        } else {
                            $this->relationBuilder->addManyToOneRelation(
                                $entityConfig,
                                $enumValueClassName,
                                $fieldName,
                                'id',
                                $fieldOptions,
                                $fieldType
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $enumValueClassName The full class name of an entity is used to store enum values
     * @param string $enumCode           The unique identifier of an enum
     * @param bool   $isMultiple         Indicates whether several options can be selected for this enum
     *                                   or it supports only one selected option
     * @param bool   $isPublic           Indicates whether this enum can be used by any entity or
     *                                   it is designed to use in one entity only
     */
    protected function createEnumValueConfigEntityModel($enumValueClassName, $enumCode, $isMultiple, $isPublic)
    {
        if ($this->configManager->hasConfigEntityModel($enumValueClassName)) {
            $this->relationBuilder->updateEntityConfigs(
                $enumValueClassName,
                [
                    'enum' => [
                        'public' => $isPublic
                    ]
                ]
            );

            return;
        }

        // create entity
        $this->configManager->createConfigEntityModel($enumValueClassName, ConfigModelManager::MODE_READONLY);
        $this->relationBuilder->updateEntityConfigs(
            $enumValueClassName,
            [
                'entity'     => [
                    'label'        => ExtendHelper::getEnumTranslationKey('label', $enumCode),
                    'plural_label' => ExtendHelper::getEnumTranslationKey('plural_label', $enumCode),
                    'description'  => ExtendHelper::getEnumTranslationKey('description', $enumCode)
                ],
                'extend'     => [
                    'owner'     => ExtendScope::OWNER_SYSTEM,
                    'is_extend' => true,
                    'table'     => $this->nameGenerator->generateEnumTableName($enumCode),
                    'inherit'   => 'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue'
                ],
                'grouping'   => [
                    'groups' => ['enum', 'dictionary']
                ],
                'enum'       => [
                    'code'     => $enumCode,
                    'public'   => $isPublic,
                    'multiple' => $isMultiple
                ],
                'dictionary' => [
                    'virtual_fields' => ['id', 'name']
                ]
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
                ]
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
                ]
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
                ]
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
                ]
            ]
        );
    }
}
