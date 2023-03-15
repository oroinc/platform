<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Form\Type;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for filter by an entity for a datasource based on a search index.
 */
class SearchEntityFilterType extends AbstractType
{
    private EntityNameResolver $entityNameResolver;
    private LocalizationHelper $localizationHelper;
    private Localization|null|false $currentLocalization = false;

    public function __construct(EntityNameResolver $entityNameResolver, LocalizationHelper $localizationHelper)
    {
        $this->entityNameResolver = $entityNameResolver;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'field_options' => [
                'multiple' => true,
                'choice_label' => function (object $entity): string {
                    return $this->entityNameResolver->getName($entity, null, $this->getCurrentLocalization());
                }
            ],
            'choices' => null,
        ]);

        // this normalizer allows to add/override field_options options outside
        $resolver->setNormalizer(
            'field_options',
            function (Options $options, $value) {
                $value['class'] = $options['class'] ?? null;
                $value['choices'] = $options['choices'] ?? null;

                return $value;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return EntityFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_search_type_entity_filter';
    }

    private function getCurrentLocalization(): ?Localization
    {
        if (false === $this->currentLocalization) {
            $this->currentLocalization = $this->localizationHelper->getCurrentLocalization();
        }

        return $this->currentLocalization;
    }
}
