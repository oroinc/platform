<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\EntityConfigBundle\Config\Config;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;

class AttachmentExtendConfigDumperExtension extends ExtendConfigDumperExtension
{
    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
    public function preUpdate(array &$extendConfigs)
    {
        /** @var Config[] $extendConfigs */
        $extendConfigs = array_filter(
            $extendConfigs,
            function (Config $item) {
                return
                    $item->is('is_extend')
                    && $item->in('state', [ExtendScope::STATE_UPDATED, ExtendScope::STATE_NEW]);
            }
        );

        foreach ($extendConfigs as $entityExtendConfig) {
            $schemaConfig       = $entityExtendConfig->get('schema');
            $entityCustomFields = $schemaConfig['doctrine'][$entityExtendConfig->get('extend_class')]['fields'];
            if ($schemaConfig && !empty($entityCustomFields)) {
                $attachmentFields = array_filter(
                    $entityCustomFields,
                    function ($item) {
                        return in_array($item['type'], AttachmentScope::$attachmentTypes);
                    }
                );

                foreach ($attachmentFields as $fieldName => $fieldConfig) {
                    $relationKey = ExtendHelper::buildRelationKey(
                        $schemaConfig['class'],
                        $fieldName,
                        'manyToOne',
                        AttachmentScope::ATTACHMENT_ENTITY
                    );

                    if (!isset($entityExtendConfig->get('relation')[$relationKey])) {
                        $relationName = Inflector::tableize('attach_' . $fieldName);
                        $label        = ConfigHelper::getTranslationKey(
                            'entity',
                            'label',
                            AttachmentScope::ATTACHMENT_ENTITY,
                            $relationName
                        );

                        $entityClassName = $entityExtendConfig->getId()->getClassName();

                        // create field
                        $this->createField(
                            $entityClassName,
                            $relationName,
                            'manyToOne',
                            [
                                'extend' => [
                                    'owner'         => ExtendScope::OWNER_SYSTEM,
                                    'state'         => ExtendScope::STATE_NEW,
                                    'extend'        => true,
                                    'target_entity' => AttachmentScope::ATTACHMENT_ENTITY,
                                    'target_field'  => 'id',
                                    'relation_key'  => $relationKey,
                                ],
                                'entity' => [
                                    'label'       => $label,
                                    'description' => '',
                                ],
                                'view'   => [
                                    'is_displayable' => false
                                ],
                                'form'   => [
                                    'is_enabled' => false
                                ]
                            ]
                        );

                        // add relation to owning entity
                        $this->addManyToOneRelation(
                            AttachmentScope::ATTACHMENT_ENTITY,
                            $entityClassName,
                            $relationName,
                            $relationKey
                        );

                        // add relation to target entity
                        $this->addManyToOneRelationTargetSide(
                            AttachmentScope::ATTACHMENT_ENTITY,
                            $entityClassName,
                            $relationName,
                            $relationKey
                        );
                    }
                }
            }
        }

        //return;
        /*
        foreach ($targetEntities as $targetEntityClassName) {
            $relationName = $this->getRelationName($targetEntityClassName);
            $relationKey  = $this->getRelationKey($entityClassName, $relationName, $targetEntityClassName);

            $entityConfigProvider = $this->configManager->getProvider('entity');
            $targetEntityConfig   = $entityConfigProvider->getConfig($targetEntityClassName);

            $label       = $targetEntityConfig->get(
                'label',
                false,
                ConfigHelper::getTranslationKey('entity', 'label', $targetEntityClassName, $relationName)
            );
            $description = ConfigHelper::getTranslationKey(
                'entity',
                'description',
                $targetEntityClassName,
                $relationName
            );

            $targetEntityPrimaryKeyColumns = $this->getPrimaryKeyColumnNames($targetEntityClassName);
            $targetFieldName               = array_shift($targetEntityPrimaryKeyColumns);
        }
        */
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param array  $values
     */
    protected function createField($className, $fieldName, $fieldType, $values)
    {
        $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType);

        $this->updateFieldConfigs(
            $className,
            $fieldName,
            $values
        );
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param array  $values
     */
    protected function updateFieldConfigs($className, $fieldName, array $values)
    {
        foreach ($values as $scope => $scopeValues) {
            $configProvider = $this->configManager->getProvider($scope);
            $fieldConfig    = $configProvider->getConfig($className, $fieldName);
            foreach ($scopeValues as $code => $val) {
                $fieldConfig->set($code, $val);
            }
            $this->configManager->persist($fieldConfig);
            $this->configManager->calculateConfigChangeSet($fieldConfig);
        }
    }

    /**
     * @param string $targetEntityName
     * @param string $sourceEntityName
     * @param string $relationName
     * @param string $relationKey
     */
    protected function addManyToOneRelation(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey
    ) {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig         = $extendConfigProvider->getConfig($sourceEntityName);

        // update schema info
        $schema                            = $extendConfig->get('schema', false, []);
        $schema['relation'][$relationName] = $relationName;
        $extendConfig->set('schema', $schema);

        // update index info
        $index                = $extendConfig->get('index', false, []);
        $index[$relationName] = null;
        $extendConfig->set('index', $index);

        // add relation to config
        $relations               = $extendConfig->get('relation', false, []);
        $relations[$relationKey] = [
            'assign'          => false,
            'field_id'        => new FieldConfigId('extend', $sourceEntityName, $relationName, 'manyToOne'),
            'owner'           => true,
            'target_entity'   => $targetEntityName,
            'target_field_id' => false
        ];
        $extendConfig->set('relation', $relations);

        $extendConfigProvider->persist($extendConfig);
        $this->configManager->calculateConfigChangeSet($extendConfig);
    }

    /**
     * @param string $targetEntityName
     * @param string $sourceEntityName
     * @param string $relationName
     * @param string $relationKey
     */
    protected function addManyToOneRelationTargetSide(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey
    ) {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig         = $extendConfigProvider->getConfig($targetEntityName);

        // add relation to config
        $relations               = $extendConfig->get('relation', false, []);
        $relations[$relationKey] = [
            'assign'          => false,
            'field_id'        => false,
            'owner'           => false,
            'target_entity'   => $sourceEntityName,
            'target_field_id' => new FieldConfigId('extend', $sourceEntityName, $relationName, 'manyToOne')
        ];
        $extendConfig->set('relation', $relations);

        $extendConfigProvider->persist($extendConfig);
        $this->configManager->calculateConfigChangeSet($extendConfig);
    }
}
