<?php

namespace Oro\Bundle\EntityMergeBundle\Twig;

use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param AccessorInterface $accessor
     * @param MergeRenderer $fieldValueRenderer
     * @param TranslatorInterface $translator
     */
    public function __construct(
        AccessorInterface $accessor,
        MergeRenderer $fieldValueRenderer,
        TranslatorInterface $translator
    ) {
        $this->accessor = $accessor;
        $this->fieldValueRenderer = $fieldValueRenderer;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'oro_entity_merge_sort_fields',
                array($this, 'sortMergeFields')
            )
        );
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
     * @param FormView[] $fields
     * @return FormView[]
     */
    public function sortMergeFields(array $fields)
    {
        usort(
            $fields,
            function ($first, $second) {
                $firstLabel = isset($first->vars['label']) ?
                    $this->translator->trans($first->vars['label']) : $first->vars['name'];

                $secondLabel = isset($second->vars['label']) ?
                    $this->translator->trans($second->vars['label']) : $second->vars['name'];

                return strnatcasecmp($firstLabel, $secondLabel);
            }
        );

        return $fields;
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
