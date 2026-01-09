<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\Metadata;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

/**
 * Initializes merge modes for field metadata during entity merge configuration.
 *
 * Listens to entity metadata creation events and assigns default merge modes to fields
 * that don't have explicit modes configured. All fields support the `REPLACE` mode, while
 * collection fields additionally support the `UNITE` mode, allowing users to choose between
 * replacing or combining collection values during merge operations.
 */
class MergeModesListener
{
    public function onCreateMetadata(EntityMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();

        foreach ($entityMetadata->getFieldsMetadata() as $fieldMetadata) {
            $mergeModes = $fieldMetadata->getMergeModes();
            if (empty($mergeModes)) {
                $this->initMergeModes($fieldMetadata);
            }
        }
    }

    /**
     * @param FieldMetadata $fieldMetadata
     * @return array
     */
    protected function initMergeModes(FieldMetadata $fieldMetadata)
    {
        $fieldMetadata->addMergeMode(MergeModes::REPLACE);

        if ($fieldMetadata->isCollection()) {
            $fieldMetadata->addMergeMode(MergeModes::UNITE);
        }
    }
}
