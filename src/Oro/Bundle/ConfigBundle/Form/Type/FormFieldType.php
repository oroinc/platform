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
                'target_field'              => array(
                    'type'    => 'text',
                    'options' => array(),
                ),
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

        if ($options['target_field'] instanceof FieldNodeDefinition) {
            $filedOptions = $options['target_field']->getOptions();
            unset($filedOptions['block']);
            unset($filedOptions['subblock']);
            $options['target_field'] = array(
                'type'    => $options['target_field']->getType(),
                'options' => $filedOptions
            );
        }
        if ($options['is_parent_scope_available']) {
            $builder->add('use_parent_scope_value', 'checkbox', $useParentOptions);
        }
        $builder->add('value', $options['target_field']['type'], $options['target_field']['options']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_config_form_field_type';
    }
}
