<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;

class FileEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
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
                if (in_array($fieldConfigId->getFieldType(), ['file', 'image'])) {
                    // create a relation
                    $relationKey = $this->relationBuilder->addManyToOneRelation(
                        $entityConfig,
                        'Oro\Bundle\AttachmentBundle\Entity\File',
                        $fieldConfigId->getFieldName(),
                        'id',
                        [
                            'importexport' => [
                                'process_as_scalar' => true
                            ]
                        ],
                        $fieldConfigId->getFieldType()
                    );

                    // set cascade persist
                    $relations                          = $entityConfig->get('relation');
                    $cascade                            = isset($relations[$relationKey]['cascade'])
                        ? $relations[$relationKey]['cascade']
                        : [];
                    $cascade[]                          = 'persist';
                    $relations[$relationKey]['cascade'] = $cascade;
                    $entityConfig->set('relation', $relations);
                    $extendConfigProvider->persist($entityConfig);
                }
            }
        }
    }
}
