<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for global fields of system configuration.
 */
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
                'use_parent_field_options' => [],
                'target_field_type' => TextType::class,
                'target_field_alias' => 'text',
                'resettable'           => true,
                'parent_checkbox_label' => ''
            ]
        );

        // adds same class for config with "resettable: true", as have config with "resettable: false"
        $resolver->setNormalizer('attr', function (Options $options, $attr) {
            if (!$attr) {
                $attr = [];
            }

            if (!isset($attr['class'])) {
                $attr['class'] = '';
            }

            $attr['class'] = sprintf('%s control-group-%s', $attr['class'], $options['target_field_alias']);

            return $attr;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $useParentOptions = $options['use_parent_field_options'];
        $useParentType    = ParentScopeCheckbox::class;
        if (!$options['resettable']) {
            $useParentOptions = ['data' => 0];
            $useParentType    = HiddenType::class;
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
                $parentValueDisabled = $form->get('value')->getConfig()->getOption('disabled');
                $disabled = isset($data['use_parent_scope_value']) ? $data['use_parent_scope_value'] : false;
                $disabled = $disabled || $parentValueDisabled;
                FormUtils::replaceField($form, 'value', ['disabled' => $disabled]);
                if ($parentValueDisabled) {
                    FormUtils::replaceField($form, 'use_parent_scope_value', ['disabled' => $disabled]);
                }
            }
        );

        // Update field again to apply the submitted 'use_parent_scope_value' state and properly map 'value' to entity
        $builder->get('use_parent_scope_value')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm()->getParent();
                $disabled = $event->getForm()->getData();
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
