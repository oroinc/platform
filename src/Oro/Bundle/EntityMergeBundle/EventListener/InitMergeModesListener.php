<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener;

use Oro\Bundle\EntityMergeBundle\Event\CreateMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

class InitMergeModesListener
{
    /**
     * @var CreateMetadataEvent
     */
    protected $event;

    /**
     * @param CreateMetadataEvent $event
     */
    public function onCreateMetadata(CreateMetadataEvent $event)
    {
        $entityMetadata = $event->getEntityMetadata();

        foreach ($entityMetadata->getFieldsMetadata() as $fieldMetadata) {
            $this->initMergeModes($fieldMetadata);
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
            $fieldMetadata->addMergeMode(MergeModes::MERGE);
        }
    }
}
