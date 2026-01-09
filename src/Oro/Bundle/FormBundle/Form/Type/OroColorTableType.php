<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\ConditionalReverseTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for color table/palette selection.
 *
 * This type provides a color picker interface with table-based color selection,
 * supporting various picker controls (hue, brightness, saturation, wheel). It handles
 * JSON serialization of color data and applies data transformers to ensure proper
 * value handling across form submission cycles.
 */
class OroColorTableType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // ConditionalReverseTransformer is a workaround (see BAP-6156)
        $builder->addModelTransformer(
            new ConditionalReverseTransformer(
                new ArrayToJsonTransformer(),
                function ($value) {
                    return !is_array($value);
                }
            )
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    'picker_control' => null // hue, brightness, saturation, or wheel. defaults wheel
                ]
            );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // Make sure a value is processed by transformers; a problem happens after submitting disabled form.
        // This is a workaround (see BAP-6156)
        if (is_array($view->vars['value'])) {
            $value = $view->vars['value'];
            foreach ($form->getConfig()->getModelTransformers() as $transformer) {
                $value = $transformer->transform($value);
            }
            foreach ($form->getConfig()->getViewTransformers() as $transformer) {
                $value = $transformer->transform($value);
            }
            $view->vars['value'] = $value;
        }

        $view->vars['configs']['table']  = true;
        $view->vars['configs']['picker'] = [];
        if ($options['picker_control']) {
            $view->vars['configs']['picker']['control'] = $options['picker_control'];
        }
    }

    #[\Override]
    public function getParent(): ?string
    {
        return HiddenType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_color_table';
    }
}
