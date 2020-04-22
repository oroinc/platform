<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extends Symfony\Component\Form\Extension\Core\Type\ChoiceType
 * Adds default configure options with choices.
 */
class LinkTargetType extends AbstractType
{
    private const NAME = 'oro_link_target';

    public const SAME_WINDOW_VALUE = 1;
    public const NEW_WINDOW_VALUE = 0;

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'oro.form.link_target.label',
            'tooltip' => 'oro.form.link_target.tooltip',
            'required' => false,
            'placeholder' => false,
            'choices' => [
                'oro.form.link_target.value.same_window' => self::SAME_WINDOW_VALUE,
                'oro.form.link_target.value.new_window' => self::NEW_WINDOW_VALUE,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
