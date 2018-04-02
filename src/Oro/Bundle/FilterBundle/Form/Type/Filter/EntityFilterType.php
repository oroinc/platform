<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityFilterType extends AbstractChoiceType
{
    const NAME = 'oro_type_entity_filter';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'field_type'    => EntityType::class,
                'field_options' => array(),
                'translatable'  => false,
            )
        );

        $resolver->setNormalizer(
            'field_type',
            function (Options $options, $value) {
                if (!empty($options['translatable'])) {
                    $value = TranslatableEntityType::class;
                }

                return $value;
            }
        );
    }
}
