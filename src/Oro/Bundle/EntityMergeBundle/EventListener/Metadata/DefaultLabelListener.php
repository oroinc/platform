<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\Metadata;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

/**
 * Initializes default labels for entity and field metadata during merge operations.
 *
 * Listens to entity metadata creation events and populates missing labels from the
 * entity configuration. For entities without explicit labels, it uses the plural label
 * from the entity config. For fields, it retrieves appropriate labels based on whether
 * the field is defined by the source entity or is a collection, ensuring consistent
 * labeling throughout the merge interface.
 */
class DefaultLabelListener
{
    const CONFIG_ENTITY_SCOPE = 'entity';

    /**
     * @var EntityConfigHelper
     */
    protected $entityConfigHelper;

    public function __construct(EntityConfigHelper $entityConfigHelper)
    {
        $this->entityConfigHelper = $entityConfigHelper;
    }

    public function onCreateMetadata(EntityMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();
        $className = $entityMetadata->getClassName();
        $entityConfig = $this->entityConfigHelper->getConfig(self::CONFIG_ENTITY_SCOPE, $className, null);

        if ($entityConfig && !$entityMetadata->has('label')) {
            $entityMetadata->set('label', $entityConfig->get('plural_label'));
        }

        foreach ($entityMetadata->getFieldsMetadata() as $fieldMetadata) {
            $this->initFieldMetadataDefaultLabel($fieldMetadata);
        }
    }

    /**
     * @param FieldMetadata $fieldMetadata
     * @return array
     */
    protected function initFieldMetadataDefaultLabel(FieldMetadata $fieldMetadata)
    {
        if ($fieldMetadata->has('label')) {
            return;
        }

        $labelCode = 'label';
        $className = $fieldMetadata->getSourceClassName();
        $fieldName = $fieldMetadata->getSourceFieldName();

        if (!$fieldMetadata->isDefinedBySourceEntity()) {
            $fieldName = null;
            if ($fieldMetadata->isCollection()) {
                $labelCode = 'plural_label';
            }
        }

        $entityConfig = $this->entityConfigHelper->getConfig(self::CONFIG_ENTITY_SCOPE, $className, $fieldName);

        if ($entityConfig) {
            $fieldMetadata->set('label', $entityConfig->get($labelCode));
        }
    }
}
