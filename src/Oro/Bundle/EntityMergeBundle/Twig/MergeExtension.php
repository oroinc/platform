<?php

namespace Oro\Bundle\EntityMergeBundle\Twig;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;

class MergeExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_entity_merge_render_field_value', array($this, 'renderMergeFieldValue')),
        );
    }

    public function renderMergeFieldValue(FieldData $fieldData, $entityOffset)
    {
        $metadata = $fieldData->getMetadata();
        $entity = $fieldData->getEntityData()->getEntityByOffset($entityOffset);
        $fieldName = $metadata->getFieldName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_merge';
    }
}
