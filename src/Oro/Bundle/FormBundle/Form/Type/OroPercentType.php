<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\PercentToLocalizedStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for percentage values.
 */
class OroPercentType extends AbstractType
{
    const NAME = 'oro_percent';

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
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return PercentType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setAllowedTypes('scale', ['null', 'int']);
        $resolver->setDefaults([
            'scale' => null,
            'rounding_mode' => \NumberFormatter::ROUND_CEILING
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers()
            ->addViewTransformer(
                new PercentToLocalizedStringTransformer($options['scale'], $options['type'])
            );
    }
}
