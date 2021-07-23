<?php

namespace Oro\Bundle\EntityMergeBundle\Twig;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides Twig functions for rendering entity merge form:
 *   - oro_entity_merge_render_field_value
 *   - oro_entity_merge_render_entity_label
 *
 * Provides a Twig filter for sorting fields on entity merge form:
 *   - oro_entity_merge_sort_fields
 */
class MergeExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

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
        return $this->container->get(TranslatorInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
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
            new TwigFunction(
                'oro_entity_merge_render_field_value',
                [$this, 'renderMergeFieldValue'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
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
    public static function getSubscribedServices()
    {
        return [
            'oro_entity_merge.accessor' => AccessorInterface::class,
            'oro_entity_merge.twig.renderer' => MergeRenderer::class,
            TranslatorInterface::class,
        ];
    }
}
