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
        return $actionType === ExtendConfigDumper::ACTION_POST_UPDATE;
    }

    /**
     * @param Config[] $extendConfigs
     */
    public function postUpdate(array &$extendConfigs)
    {
        foreach ($extendConfigs as &$entityExtendConfig) {
            if (!$entityExtendConfig->is('is_extend')) {
                continue;
            }

            $entityClassName = $entityExtendConfig->getId()->getClassName();

            /** @var FieldConfigId[] $entityCustomFields */
            $entityCustomFields = $this->configManager->getProvider('extend')->getIds($entityClassName);

            $attachmentFields = [];
            if (!empty($entityCustomFields)) {
                /** @var FieldConfigId[] $attachmentFields */
                $attachmentFields = array_filter(
                    $entityCustomFields,
                    function (FieldConfigId $item) {
                        return in_array($item->getFieldType(), AttachmentScope::$attachmentTypes);
                    }
                );
            }

            if (!empty($attachmentFields)) {
                foreach ($attachmentFields as $fieldConfig) {
                    $attachmentFieldName = $fieldConfig->getFieldName();

                    $this->cleanUpAttachmentType($entityExtendConfig, $attachmentFieldName);

                    $relationKey = ExtendHelper::buildRelationKey(
                        $entityClassName,
                        $fieldConfig->getFieldName(),
                        'manyToOne',
                        AttachmentScope::ATTACHMENT_ENTITY
                    );

                    if (!isset($entityExtendConfig->get('relation')[$relationKey])) {
                        $relationName = Inflector::tableize($attachmentFieldName);
                        $label        = ConfigHelper::getTranslationKey(
                            'entity',
                            'label',
                            $entityClassName,
                            $attachmentFieldName
                        );

                        // create field
                        $this->createField(
                            $entityClassName,
                            $relationName,
                            'manyToOne',
                            [
                                'extend' => [
                                    'owner'         => ExtendScope::OWNER_CUSTOM,
                                    'state'         => ExtendScope::STATE_ACTIVE,
                                    'is_extend'     => true,
                                    'target_entity' => AttachmentScope::ATTACHMENT_ENTITY,
                                    'target_field'  => 'id',
                                    'relation_key'  => $relationKey,
                                ],
                                'entity' => [
                                    'label'       => $label,
                                    'description' => '',
                                ],
                            ]
                        );

                        // add relation to owning entity
                        $this->addManyToOneRelation(
                            AttachmentScope::ATTACHMENT_ENTITY,
                            $entityClassName,
                            $relationName,
                            $relationKey
                        );
                    }
                }

                $this->configManager->persist($entityExtendConfig);
                $this->configManager->flush();
            }
        }
    }

    /**
     * Field of attachment type should not be passed to dumper.
     *
     * @param Config $entityExtendConfig
     * @param string $attachmentFieldName
     */
    protected function cleanUpAttachmentType(
        Config $entityExtendConfig,
        $attachmentFieldName
    ) {
        $schemaConfig = $entityExtendConfig->get('schema');
        $extendClass  = $entityExtendConfig->get('extend_class');
        unset($schemaConfig['doctrine'][$extendClass]['fields'][$attachmentFieldName]);
        unset($schemaConfig['property'][$attachmentFieldName]);

        $entityExtendConfig->set('schema', $schemaConfig);

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfigProvider->persist($entityExtendConfig);
        $this->configManager->calculateConfigChangeSet($entityExtendConfig);
    }

    /**
     *  TODO:
     *      Next methods (createField, updateFieldConfigs, addManyToOneRelation)
     *      is copy-past from AssociationExtendConfigDumperExtension
     *      and as discussed with Bravo team will be refactored.
     *
     */

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param array  $values
     */
    protected function createField($className, $fieldName, $fieldType, $values)
    {
        $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType, 'hidden');
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
            'assign'          => true,
            'field_id'        => new FieldConfigId('extend', $sourceEntityName, $relationName, 'manyToOne'),
            'owner'           => true,
            'target_entity'   => $targetEntityName,
            'target_field_id' => false
        ];
        $extendConfig->set('relation', $relations);

        $extendConfigProvider->persist($extendConfig);
        $this->configManager->calculateConfigChangeSet($extendConfig);
    }
}
