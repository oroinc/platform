<?php

namespace Oro\Bundle\TranslationBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class TranslatableChoiceTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'is_translated_group'   => false, // @deprecated since 1.5. Will be removed in 2.0
                'is_translated_option'  => false, // @deprecated since 1.5. Will be removed in 2.0
                'is_translated_groups'  => false,
                'is_translated_choices' => false
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['is_translated_groups'] || $options['is_translated_group']) {
            $view->vars['is_translated_groups'] = true;
        }
        if ($options['is_translated_choices'] || $options['is_translated_option']) {
            $view->vars['is_translated_choices'] = true;
        }
    }
}
