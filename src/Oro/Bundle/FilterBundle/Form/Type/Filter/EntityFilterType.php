<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for filter by an entity.
 */
class EntityFilterType extends AbstractChoiceType
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'field_type'    => EntityType::class,
            'field_options' => [],
            'translatable'  => false,
        ]);

        $resolver->setNormalizer(
            'field_type',
            function (Options $options, $value) {
                if (!empty($options['translatable'])) {
                    $value = TranslatableEntityType::class;
                }

                return $value;
            }
        );
        $resolver->setNormalizer(
            'field_options',
            function (Options $options, $value) {
                if (!isset($value['translatable_options'])) {
                    $value['translatable_options'] = false;
                }

                return $value;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return ChoiceFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_type_entity_filter';
    }
}
