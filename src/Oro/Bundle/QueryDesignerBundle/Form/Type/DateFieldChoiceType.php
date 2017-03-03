<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DateFieldChoiceType extends FieldChoiceType
{
    const NAME = 'oro_date_field_choice';

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['placeholder']            = $options['placeholder'];
        $view->vars['entity_choice_selector'] = $options['entity_choice_selector'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'placeholder'            => 'oro.entity.form.choose_entity_field',
                'entity_choice_selector' => null
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
