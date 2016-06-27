<?php

namespace Oro\Bundle\ConfigBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Disables or enables the 'value' field of resettable forms
 * depending on the state of 'use_parent_scope_value' checkbox
 */
class FormFieldType extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (empty($options['resettable'])) {
            return;
        }
        $this->addListeners($builder, $options);
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
     * @param array $targetFieldOptions
     */
    private function setTargetFieldDisabled(FormInterface $form, $data, $targetFieldType, array $targetFieldOptions)
    {
        if (isset($data['use_parent_scope_value'])) {
            $targetFieldOptions['disabled'] = $data['use_parent_scope_value'];
        }

        // Re-add the 'value' field to override its definition
        $form->add('value', $targetFieldType, $targetFieldOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_config_form_field_type';
    }
}
