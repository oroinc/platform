<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An enum value selector based on 'choice' form type
 */
class EnumChoiceType extends AbstractEnumType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'placeholder' => null,
                'empty_data'  => static fn (Options $options) => $options['multiple'] ? [] : null,
            ]
        );

        $resolver->setNormalizer(
            'placeholder',
            function (Options $options, $value) {
                return (null === $value) && !$options['expanded'] && !$options['multiple']
                    ? 'oro.form.choose_value'
                    : $value;
            }
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return TranslatableEntityType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_enum_choice';
    }
}
