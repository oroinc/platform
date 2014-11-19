<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\ConditionalReverseTransformer;

class OroColorTableType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new ConditionalReverseTransformer(
                new ArrayToJsonTransformer(),
                function ($value) {
                    return !is_array($value);
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    'picker_control' => null // hue, brightness, saturation, or wheel. defaults wheel
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // make sure a value is processed by transformers; a problem happens after submitting disabled form
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_color_table';
    }
}
