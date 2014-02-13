<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class InitDefaultLabelListener
{
    /**
     * @var ConfigProviderInterface
     */
    protected $entityConfig;

    /**
     * @param ConfigProviderInterface $entityConfig
     */
    public function __construct(ConfigProviderInterface $entityConfig)
    {
        $this->entityConfig = $entityConfig;
    }

    /**
     * @param EntityMetadataEvent $event
     */
    public function onCreateMetadata(EntityMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();

        foreach ($entityMetadata->getFieldsMetadata() as $fieldMetadata) {
            $this->initDefaultLabel($fieldMetadata);
        }
    }

    /**
     * @param FieldMetadata $fieldMetadata
     * @return array
     */
    protected function initDefaultLabel(FieldMetadata $fieldMetadata)
    {
        if ($fieldMetadata->has('label') || !$fieldMetadata->hasDoctrineMetadata()) {
            return;
        }

        if ($fieldMetadata->getDoctrineMetadata()->isMappedBySourceEntity()) {
            $fieldMetadata->set(
                'label',
                $this->entityConfig->getConfig(
                    $fieldMetadata->getEntityMetadata()->getClassName(),
                    $fieldMetadata->getFieldName()
                )->get('label')
            );
        }
    }
}
