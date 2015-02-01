<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormFieldType extends AbstractType
{
    /**
     * Pass target field options to field form type
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'target_field_options' => array(),
                'target_field_type'    => 'text',
                'resettable'           => true,
                'cascade_validation'   => true,
                'parent_checkbox_label' => ''
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $useParentOptions = [];
        $useParentType    = 'oro_config_parent_scope_checkbox_type';
        if (!$options['resettable']) {
            $useParentOptions = ['data' => 0];
            $useParentType    = 'hidden';
        }
        $useParentOptions['label'] = $options['parent_checkbox_label'];

        $builder->add('use_parent_scope_value', $useParentType, $useParentOptions);
        $builder->add('value', $options['target_field_type'], $options['target_field_options']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_config_form_field_type';
    }
}
