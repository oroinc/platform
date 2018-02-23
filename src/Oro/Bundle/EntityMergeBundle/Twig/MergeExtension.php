<?php

namespace Oro\Bundle\EntityMergeBundle\Twig;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;

class MergeExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return AccessorInterface
     */
    protected function getAccessor()
    {
        return $this->container->get('oro_entity_merge.accessor');
    }

    /**
     * @return MergeRenderer
     */
    protected function getFieldValueRenderer()
    {
        return $this->container->get('oro_entity_merge.twig.renderer');
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_entity_merge_sort_fields',
                [$this, 'sortMergeFields']
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_entity_merge_render_field_value',
                [$this, 'renderMergeFieldValue'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_entity_merge_render_entity_label',
                [$this, 'renderMergeEntityLabel'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Render value of merge field
     *
     * @param FormView[] $fields
     *
     * @return FormView[]
     */
    public function sortMergeFields(array $fields)
    {
        $translator = $this->getTranslator();
        usort(
            $fields,
            function ($first, $second) use ($translator) {
                $firstLabel = isset($first->vars['label'])
                    ? $translator->trans($first->vars['label'])
                    : $first->vars['name'];

                $secondLabel = isset($second->vars['label'])
                    ? $translator->trans($second->vars['label'])
                    : $second->vars['name'];

                return strnatcasecmp($firstLabel, $secondLabel);
            }
        );

        return $fields;
    }

    /**
     * Render value of merge field
     *
     * @param FieldData $fieldData
     * @param int       $entityOffset
     *
     * @return string
     */
    public function renderMergeFieldValue(FieldData $fieldData, $entityOffset)
    {
        $entity = $fieldData->getEntityData()->getEntityByOffset($entityOffset);
        $metadata = $fieldData->getMetadata();
        $value = $this->getAccessor()->getValue($entity, $metadata);

        return $this->getFieldValueRenderer()->renderFieldValue($value, $metadata, $entity);
    }

    /**
     * Render label of merge entity
     *
     * @param EntityData $entityData
     * @param int        $entityOffset
     *
     * @return string
     */
    public function renderMergeEntityLabel(EntityData $entityData, $entityOffset)
    {
        $entity = $entityData->getEntityByOffset($entityOffset);
        $metadata = $entityData->getMetadata();

        return $this->getFieldValueRenderer()->renderEntityLabel($entity, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_merge';
    }
}
