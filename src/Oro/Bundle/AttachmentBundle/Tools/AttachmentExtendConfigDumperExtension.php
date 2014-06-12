<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;

class AttachmentExtendConfigDumperExtension extends ExtendConfigDumperExtension
{
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

                    $b = 1;
                }



            }
        }

        return;

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
                        'target_entity' => $targetEntityClassName,
                        'target_field'  => $targetFieldName,
                        'relation_key'  => $relationKey,
                    ],
                    'entity' => [
                        'label'       => $label,
                        'description' => $description,
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
                $targetEntityClassName,
                $entityClassName,
                $relationName,
                $relationKey
            );

            // add relation to target entity
            $this->addManyToOneRelationTargetSide(
                $targetEntityClassName,
                $entityClassName,
                $relationName,
                $relationKey
            );
        }
    }
}
