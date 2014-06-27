<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;

class AttachmentEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var RelationBuilder */
    protected $relationBuilder;

    /**
     * @param RelationBuilder $relationBuilder
     */
    public function __construct(RelationBuilder $relationBuilder)
    {
        $this->relationBuilder = $relationBuilder;
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
        $configManager = $this->relationBuilder->getConfigManager();
        foreach ($extendConfigs as &$entityExtendConfig) {
            if (!$entityExtendConfig->is('is_extend')) {
                continue;
            }

            $entityClassName = $entityExtendConfig->getId()->getClassName();

            /** @var FieldConfigId[] $entityCustomFields */
            $entityCustomFields = $configManager->getProvider('extend')->getIds($entityClassName);

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
                        // add relation to owning entity
                        $relationName = Inflector::tableize($attachmentFieldName);
                        $this->relationBuilder->addManyToOneRelation(
                            AttachmentScope::ATTACHMENT_ENTITY,
                            $entityClassName,
                            $relationName,
                            $relationKey,
                            ['assign' => true]
                        );

                        $this->relationBuilder->updateFieldConfig(
                            $entityClassName,
                            $relationName,
                            [
                                'importexport' => [
                                    'process_as_scalar' => true
                                ]
                            ]
                        );
                    }
                }

                $configManager->persist($entityExtendConfig);
                $configManager->flush();
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

        $configManager        = $this->relationBuilder->getConfigManager();
        $extendConfigProvider = $configManager->getProvider('extend');

        $extendConfigProvider->persist($entityExtendConfig);
        $configManager->calculateConfigChangeSet($entityExtendConfig);
    }
}
