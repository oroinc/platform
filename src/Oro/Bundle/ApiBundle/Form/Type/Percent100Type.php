<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Form\DataTransformer\Percent100ToLocalizedStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for fields that represent a percentage value multiplied by 100.
 */
class Percent100Type extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new Percent100ToLocalizedStringTransformer(
            $options['scale'],
            $options['grouping'],
            $options['rounding_mode']
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('scale', null) // default scale is locale specific (usually around 3)
            ->setDefault('grouping', false)
            ->setDefault('rounding_mode', NumberToLocalizedStringTransformer::ROUND_HALF_UP)
            ->setDefault('compound', false)
            ->setAllowedValues('rounding_mode', [
                NumberToLocalizedStringTransformer::ROUND_FLOOR,
                NumberToLocalizedStringTransformer::ROUND_DOWN,
                NumberToLocalizedStringTransformer::ROUND_HALF_DOWN,
                NumberToLocalizedStringTransformer::ROUND_HALF_EVEN,
                NumberToLocalizedStringTransformer::ROUND_HALF_UP,
                NumberToLocalizedStringTransformer::ROUND_UP,
                NumberToLocalizedStringTransformer::ROUND_CEILING,
            ])
            ->setAllowedTypes('scale', ['null', 'int']);
    }
}
