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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFilters()
    {
        return [
            new TwigFilter('oro_entity_merge_sort_fields', [$this, 'sortMergeFields'])
        ];
    }

    #[\Override]
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
     * @param FormView[] $fields
     *
     * @return FormView[]
     */
    public function sortMergeFields(array $fields): array
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

    public function renderMergeFieldValue(FieldData $fieldData, int $entityOffset): string
    {
        $entity = $fieldData->getEntityData()->getEntityByOffset($entityOffset);
        $metadata = $fieldData->getMetadata();
        $value = $this->getAccessor()->getValue($entity, $metadata);

        return $this->getFieldValueRenderer()->renderFieldValue($value, $metadata, $entity);
    }

    public function renderMergeEntityLabel(EntityData $entityData, int $entityOffset): string
    {
        return $this->getFieldValueRenderer()->renderEntityLabel(
            $entityData->getEntityByOffset($entityOffset),
            $entityData->getMetadata()
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_entity_merge.accessor' => AccessorInterface::class,
            MergeRenderer::class,
            TranslatorInterface::class
        ];
    }

    private function getAccessor(): AccessorInterface
    {
        return $this->container->get('oro_entity_merge.accessor');
    }

    private function getFieldValueRenderer(): MergeRenderer
    {
        return $this->container->get(MergeRenderer::class);
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }
}
