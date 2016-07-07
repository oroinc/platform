<?php

namespace Oro\Bundle\ConfigBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\FormBundle\Utils\FormUtils;

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

        // Initially disable/enable 'value' field depending on the checkbox 'use_parent_scope_value'
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                FormUtils::replaceField($form, 'value', ['disabled' => $data['use_parent_scope_value']]);
            }
        );

        // Update field again to apply the submitted 'use_parent_scope_value' state and properly map 'value' to entity
        $builder->get('use_parent_scope_value')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm()->getParent();
                $data = $event->getForm()->getData();
                FormUtils::replaceField($form, 'value', ['disabled' => $data['use_parent_scope_value']]);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_config_form_field_type';
    }
}
