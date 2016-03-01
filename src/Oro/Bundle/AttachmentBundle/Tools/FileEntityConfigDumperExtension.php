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
                if (!$fieldConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_UPDATE])) {
                    continue;
                }

                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                if (in_array($fieldConfigId->getFieldType(), ['file', 'image'])) {
                    $cascade = $fieldConfig->get('cascade', false, []);
                    if (!in_array('persist', $cascade, true)) {
                        $cascade[] = 'persist';
                    }
                    $this->relationBuilder->addManyToOneRelation(
                        $entityConfig,
                        'Oro\Bundle\AttachmentBundle\Entity\File',
                        $fieldConfigId->getFieldName(),
                        'id',
                        [
                            'extend'       => [
                                'cascade' => $cascade
                            ],
                            'importexport' => [
                                'process_as_scalar' => true
                            ]
                        ],
                        $fieldConfigId->getFieldType()
                    );
                }
            }
        }
    }
}
