<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ConfigBundle\Config\Tree\FieldNodeDefinition;

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
                'target_field_options'      => array(),
                'target_field_type'         => 'text',
                'is_parent_scope_available' => true,
                'cascade_validation'        => true,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $useParentOptions = array('required' => false, 'label' => 'Use default');
        if ($options['is_parent_scope_available']) {
            $builder->add('use_parent_scope_value', 'checkbox', $useParentOptions);
        }
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
