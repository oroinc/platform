<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Utils\FormUtils;

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
            $this->addFieldDisableListeners($builder);
        }
    }

    /**
     * Add listeners that disable fields according to the state of `use_parent_scope_value` checkbox
     *
     * @param FormBuilderInterface $builder
     */
    protected function addFieldDisableListeners(FormBuilderInterface $builder)
    {
        // Initially disable/enable 'value' field depending on the checkbox 'use_parent_scope_value'
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $disabled = isset($data['use_parent_scope_value']) ? $data['use_parent_scope_value'] : false;
                FormUtils::replaceField($form, 'value', ['disabled' => $disabled]);
            }
        );

        // Update field again to apply the submitted 'use_parent_scope_value' state and properly map 'value' to entity
        $builder->get('use_parent_scope_value')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm()->getParent();
                $data = $event->getForm()->getData();
                $disabled = isset($data['use_parent_scope_value']) ? $data['use_parent_scope_value'] : false;
                FormUtils::replaceField($form, 'value', ['disabled' => $disabled]);
            }
        );
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_config_form_field_type';
    }
}
