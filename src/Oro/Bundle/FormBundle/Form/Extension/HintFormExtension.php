<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;

/**
 * Adds hint support for fields
 * Specify text in the 'hint' option and html attributes in `hint_attr`
 */
class HintFormExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['hint']);
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
    }
}
