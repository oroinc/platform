<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An enum value selector based on 'choice' form type
 */
class EnumChoiceType extends AbstractEnumType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'empty_value' => null,
                'empty_data'  => null
            ]
        );

        $resolver->setNormalizers(
            [
                'empty_value' => function (Options $options, $value) {
                    return (null === $value) && !$options['expanded'] && !$options['multiple']
                        ? 'oro.form.choose_value'
                        : $value;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'translatable_entity';
    }

    /**
     * {@inheritdoc}
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
        return 'oro_enum_choice';
    }
}
