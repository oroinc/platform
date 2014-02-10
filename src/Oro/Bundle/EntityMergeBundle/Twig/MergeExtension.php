<?php

namespace Oro\Bundle\EntityMergeBundle\Twig;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;

class MergeExtension extends \Twig_Extension
{
    /**
     * @var \Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface
     */
    protected $accessor;

    /**
     * @param AccessorInterface $accessor
     */
    public function __construct(AccessorInterface $accessor)
    {
        $this->accessor = $accessor;
    }

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
        $entity = $fieldData->getEntityData()->getEntityByOffset($entityOffset);
        $metadata = $fieldData->getMetadata();

        return $this->accessor->getValue($entity, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_merge';
    }
}
