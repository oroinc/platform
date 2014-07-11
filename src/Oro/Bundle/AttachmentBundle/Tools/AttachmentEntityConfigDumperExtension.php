<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;

class AttachmentEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var RelationBuilder */
    protected $relationBuilder;

    /**
     * @param ConfigManager   $configManager
     * @param RelationBuilder $relationBuilder
     */
    public function __construct(
        ConfigManager $configManager,
        RelationBuilder $relationBuilder
    ) {
        $this->configManager   = $configManager;
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
                        // add relation to owning entity
                        $relationName = Inflector::tableize($attachmentFieldName);
                        $this->relationBuilder->addManyToOneRelation(
                            AttachmentScope::ATTACHMENT_ENTITY,
                            $entityClassName,
                            $relationName,
                            $relationKey,
                            ['assign' => true]
                        );
                        $this->relationBuilder->updateFieldConfigs(
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
        $extendClass = $entityExtendConfig->has('extend_class') ?
            $entityExtendConfig->get('extend_class') :
            $schemaConfig['class'];

        unset($schemaConfig['doctrine'][$extendClass]['fields'][$attachmentFieldName]);
        unset($schemaConfig['property'][$attachmentFieldName]);

        $entityExtendConfig->set('schema', $schemaConfig);

        $this->configManager->persist($entityExtendConfig);
        $this->configManager->calculateConfigChangeSet($entityExtendConfig);
    }
}
