<?php

namespace Oro\Bundle\EntityMergeBundle\Twig;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;

class MergeExtension extends \Twig_Extension
{
    /**
     * @var AccessorInterface
     */
    protected $accessor;

    /**
     * @var MergeRenderer
     */
    protected $fieldValueRenderer;

    /**
     * @param AccessorInterface $accessor
     * @param MergeRenderer $fieldValueRenderer
     */
    public function __construct(AccessorInterface $accessor, MergeRenderer $fieldValueRenderer)
    {
        $this->accessor = $accessor;
        $this->fieldValueRenderer = $fieldValueRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'oro_entity_merge_render_field_value',
                array($this, 'renderMergeFieldValue'),
                array('is_safe' => array('html'))
            ),
            new \Twig_SimpleFunction(
                'oro_entity_merge_render_entity_label',
                array($this, 'renderMergeEntityLabel'),
                array('is_safe' => array('html'))
            ),
        );
    }

    /**
     * Render value of merge field
     *
     * @param FieldData $fieldData
     * @param int $entityOffset
     * @return string
     */
    public function renderMergeFieldValue(FieldData $fieldData, $entityOffset)
    {
        $entity = $fieldData->getEntityData()->getEntityByOffset($entityOffset);
        $metadata = $fieldData->getMetadata();
        $value = $this->accessor->getValue($entity, $metadata);

        return $this->fieldValueRenderer->renderFieldValue($value, $metadata, $entity);
    }

    /**
     * Render label of merge entity
     *
     * @param EntityData $entityData
     * @param int $entityOffset
     * @return string
     */
    public function renderMergeEntityLabel(EntityData $entityData, $entityOffset)
    {
        $entity = $entityData->getEntityByOffset($entityOffset);
        $metadata = $entityData->getMetadata();

        return $this->fieldValueRenderer->renderEntityLabel($entity, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_merge';
    }
}
