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
    public function buildForm(FormBuilderInterface $builder, array $options)
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // default scale is locale specific (usually around 3)
            'scale' => null,
            'grouping' => false,
            'rounding_mode' => NumberToLocalizedStringTransformer::ROUND_HALF_UP,
            'compound' => false
        ]);

        $resolver->setAllowedValues('rounding_mode', [
            NumberToLocalizedStringTransformer::ROUND_FLOOR,
            NumberToLocalizedStringTransformer::ROUND_DOWN,
            NumberToLocalizedStringTransformer::ROUND_HALF_DOWN,
            NumberToLocalizedStringTransformer::ROUND_HALF_EVEN,
            NumberToLocalizedStringTransformer::ROUND_HALF_UP,
            NumberToLocalizedStringTransformer::ROUND_UP,
            NumberToLocalizedStringTransformer::ROUND_CEILING,
        ]);
        $resolver->setAllowedTypes('scale', ['null', 'int']);
    }
}
