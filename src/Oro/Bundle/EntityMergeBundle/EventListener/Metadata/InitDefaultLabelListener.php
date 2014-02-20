<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\Metadata;

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
        $entityMetadata->set(
            'label',
            $this
                ->entityConfig
                ->getConfig($entityMetadata->getClassName())
                ->get('plural_label')
        );

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

        $labelCode = 'label';

        if ($fieldMetadata->getDoctrineMetadata()->isMappedBySourceEntity()) {
            $className = $fieldMetadata->getEntityMetadata()->getClassName();
            $fieldName = $fieldMetadata->getFieldName();
            $labelCode = 'label';
        } else {
            $className = $fieldMetadata->getDoctrineMetadata()->get('sourceEntity');
            $fieldName = null;
            if ($fieldMetadata->isCollection()) {
                $labelCode = 'plural_label';
            }
        }

        if ($this->entityConfig->hasConfig($className, $fieldName)) {
            $fieldMetadata->set(
                'label',
                $this->entityConfig->getConfig($className, $fieldName)->get($labelCode)
            );
        }
    }
}
