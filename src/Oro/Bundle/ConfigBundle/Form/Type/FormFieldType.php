<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormFieldType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'target_field_options' => [],
                'target_field_type'    => 'text',
                'resettable'           => true,
                'cascade_validation'   => true,
                'parent_checkbox_label' => ''
            ]
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

        if ($options['resettable']) {
            $this->addListeners($builder, $options);
        }
    }

    /**
     * Links the disabled state of the 'value' field and the checkbox 'use_parent_scope_value' state
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    private function addListeners(FormBuilderInterface $builder, array $options)
    {
        $targetFieldType = $options['target_field_type'];
        $targetFieldOptions = $options['target_field_options'];

        // Initially disable/enable 'value' field depending on the checkbox 'use_parent_scope_value'
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($targetFieldType, $targetFieldOptions) {
                $this->setTargetFieldDisabled(
                    $event->getForm(),
                    $event->getData(),
                    $targetFieldType,
                    $targetFieldOptions
                );
            }
        );

        // If 'use_parent_scope_value' was initially checked (then 'value' field was disabled),
        // then if it gets unchecked, we also need to enable 'value', so it will be mapped to entity
        // We have to bind to POST_SUBMIT of the checkbox to get its value and still be able to modify the form
        $builder->get('use_parent_scope_value')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($targetFieldType, $targetFieldOptions) {
                $this->setTargetFieldDisabled(
                    $event->getForm()->getParent(),
                    $event->getForm()->getData(),
                    $targetFieldType,
                    $targetFieldOptions
                );
            }
        );
    }

    /**
     * Modifies the form to set disabled state of 'value' field depending on 'use_parent_scope_value'
     *
     * @param FormInterface $form
     * @param $targetFieldType
     * @param $targetFieldOptions
     */
    private function setTargetFieldDisabled(FormInterface $form, $data, $targetFieldType, $targetFieldOptions)
    {
        if (isset($data['use_parent_scope_value'])) {
            $targetFieldOptions['disabled'] = $data['use_parent_scope_value'];
        }

        // Re-add the 'value' field to override its definition
        $form->add('value', $targetFieldType, $targetFieldOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['value_field_only'] = empty($options['resettable']) && empty($options['label']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_config_form_field_type';
    }
}
