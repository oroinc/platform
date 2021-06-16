<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\DataTransformer\NumberToLocalizedStringTransformer;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer as BaseTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Replaces Symfony NumberToLocalizedStringTransformer with Oro NumberToLocalizedStringTransformer view transformer.
 */
class NumberTypeExtension extends AbstractTypeExtension
{
    private NumberFormatter $numberFormatter;

    public function __construct(NumberFormatter $numberFormatter)
    {
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->replaceNumberToStringTransformer($builder, $options);
    }

    private function replaceNumberToStringTransformer(FormBuilderInterface $builder, array $options): void
    {
        $transformerKey = null;
        $viewTransformers = $builder->getViewTransformers();
        foreach ($viewTransformers as $key => $viewTransformer) {
            if ($viewTransformer instanceof BaseTransformer) {
                $transformerKey = $key;
                break;
            }
        }

        if (null !== $transformerKey) {
            $builder->resetViewTransformers();
            $viewTransformers[$transformerKey] = new NumberToLocalizedStringTransformer(
                $this->numberFormatter,
                $options['scale'],
                $options['grouping'],
                $options['rounding_mode'],
                // Copied from Symfony\Component\Form\Extension\Core\Type\NumberType
                // @see https://github.com/symfony/symfony/pull/30267,
                // because input type="number" doesn't work with floats in localized formats
                $options['html5'] ? 'en' : null
            );

            foreach ($viewTransformers as $key => $viewTransformer) {
                $builder->addViewTransformer($viewTransformer);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('limit_decimals', true);
        $resolver->setAllowedTypes('limit_decimals', 'bool');
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-limit-decimals'] = (int)$options['limit_decimals'];
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [NumberType::class];
    }
}
