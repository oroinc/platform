<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\RelationEntityConfigDumperExtension;

/**
 * Extension for support entity field types: File, Image, Multiple Files and Multiple Images
 */
class FileEntityConfigDumperExtension extends RelationEntityConfigDumperExtension
{
    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $entityConfigs = $this->configManager->getConfigs('extend');
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('is_extend')) {
                continue;
            }

            $fieldConfigs = $this->configManager->getConfigs('extend', $entityConfig->getId()->getClassName());
            foreach ($fieldConfigs as $fieldConfig) {
                if (!$fieldConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_UPDATE])) {
                    continue;
                }

                $this->addRelations($fieldConfig);
                $this->addMultiRelations($fieldConfig);
            }
        }
    }

    private function addRelations(ConfigInterface $extendFieldConfig)
    {
        /* @var $extendFieldConfigId FieldConfigId */
        $extendFieldConfigId = $extendFieldConfig->getId();
        if (!in_array($extendFieldConfigId->getFieldType(), ['file', 'image'])) {
            return;
        }

        $extendFieldConfig->set('target_entity', File::class);
        $extendFieldConfig->set('target_field', 'id');
        $extendFieldConfig->set('on_delete', 'SET NULL');

        $cascade = $extendFieldConfig->get('cascade', false, []);
        if (!in_array('persist', $cascade, true)) {
            $cascade[] = 'persist';
            $extendFieldConfig->set('cascade', $cascade);
        }

        $importFieldConfig = $this->configManager->getFieldConfig(
            'importexport',
            $extendFieldConfigId->getClassName(),
            $extendFieldConfigId->getFieldName()
        );
        $importFieldConfig->set('process_as_scalar', false);
        $this->configManager->persist($importFieldConfig);

        $this->createRelation($extendFieldConfig);
    }

    private function addMultiRelations(ConfigInterface $extendFieldConfig)
    {
        /* @var $extendFieldConfigId FieldConfigId */
        $extendFieldConfigId = $extendFieldConfig->getId();
        if (!in_array($extendFieldConfigId->getFieldType(), ['multiFile', 'multiImage'])) {
            return;
        }

        $extendFieldConfig->set('target_entity', FileItem::class);
        $extendFieldConfig->set('bidirectional', true);
        $extendFieldConfig->set('orphanRemoval', true);
        $extendFieldConfig->set('target_grid', ['id']);
        $extendFieldConfig->set('target_title', ['id']);
        $extendFieldConfig->set('target_detailed', ['id']);

        $cascade = array_merge(
            $extendFieldConfig->get('cascade', false, []),
            ['persist', 'remove']
        );
        $extendFieldConfig->set('cascade', array_unique($cascade));

        $importFieldConfig = $this->configManager->getFieldConfig(
            'importexport',
            $extendFieldConfigId->getClassName(),
            $extendFieldConfigId->getFieldName()
        );
        $importFieldConfig->set('full', true);
        $this->configManager->persist($importFieldConfig);

        $this->createRelation($extendFieldConfig);
    }
}
