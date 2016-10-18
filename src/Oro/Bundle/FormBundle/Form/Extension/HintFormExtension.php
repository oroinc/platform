<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds hint support for fields
 * Options:
 * `hint` specifies the text
 * `hint_attr` specifies html attributes of the div
 * `hint_position` option controls the DOM position of the hint block relative to the field (inside .control-group div)
 *  - 'above' will render it above the input row
 *  - 'below' will render it below the input row
 *  - 'after_input' will render it after the input field (inside the filed wrapper)
 */
class HintFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['hint']);
        $resolver->setDefault('hint_position', 'after_input');
        $resolver->setAllowedValues('hint_position', ['above', 'below', 'after_input']);
        $resolver->setDefaults(['hint_attr' => ['class' => 'oro-hint']]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!$form->getParent()) {
            return;
        }

        if (!isset($options['hint'])) {
            return;
        }
        $view->vars['hint'] = $options['hint'];
        $view->vars['hint_attr'] = $options['hint_attr'];
        $view->vars['hint_position'] = $options['hint_position'];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\FormType';
    }
}
