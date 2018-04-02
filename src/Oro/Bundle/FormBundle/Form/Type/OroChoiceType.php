<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroChoiceType extends AbstractType
{
    const NAME = 'oro_choice';

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'placeholder' => 'oro.form.choose_value',
            'allowClear' => true,
        ];

        $resolver->setDefaults(
            [
                'empty_data' => null,
                'configs' => $defaults,
            ]
        );

        // OptionsResolver doesn't support merging of second level.
        // Without normalizer "configs" will be overridden (not merged) by user array
        $resolver->setNormalizer(
            'configs',
            function (Options $options, $configs) use ($defaults) {
                return array_merge($defaults, $configs);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2ChoiceType::class;
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
        return self::NAME;
    }
}
