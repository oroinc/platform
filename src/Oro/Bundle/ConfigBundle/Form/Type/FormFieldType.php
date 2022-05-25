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
 * The form type that is a container for system configuration fields.
 */
class FormFieldType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'target_field_type'        => TextType::class,
            'target_field_alias'       => 'text',
            'target_field_options'     => [],
            'resettable'               => true,
            'use_parent_field_label'   => '',
            'use_parent_field_options' => [],
            'value_hint'               => null,
            'translatable_value_hint'  => true
        ]);

        // adds same class for config with "resettable: true", as have config with "resettable: false"
        $resolver->setNormalizer('attr', function (Options $options, $attr) {
            if (!$attr) {
                $attr = [];
            }

            $additionalCssClass = 'control-group-' . $options['target_field_alias'];
            $attr['class'] = !empty($attr['class'])
                ? sprintf('%s %s', $attr['class'], $additionalCssClass)
                : $additionalCssClass;

            return $attr;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $resettable = $options['resettable'];
        if ($resettable) {
            $useParentType = ParentScopeCheckbox::class;
            $useParentOptions = $options['use_parent_field_options'];
            $useParentOptions['label'] = $options['use_parent_field_label'];
        } else {
            $useParentType = HiddenType::class;
            $useParentOptions = ['data' => 0];
        }

        $builder->add('use_parent_scope_value', $useParentType, $useParentOptions);
        $builder->add('value', $options['target_field_type'], $options['target_field_options']);

        if ($resettable) {
            $this->addFieldDisableListeners($builder);
        }
    }

    /**
     * Adds listeners that disable fields according to the state of "use_parent_scope_value" checkbox.
     */
    private function addFieldDisableListeners(FormBuilderInterface $builder): void
    {
        // Initially disable/enable 'value' field depending on the checkbox 'use_parent_scope_value'
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $parentValueDisabled = $form->get('value')->getConfig()->getOption('disabled');
                $disabled = ($data['use_parent_scope_value'] ?? false) || $parentValueDisabled;
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
                $form = $event->getForm();
                FormUtils::replaceField($form->getParent(), 'value', ['disabled' => (bool)$form->getData()]);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['value_field_only'] = empty($options['resettable']) && empty($options['label']);
        if (!empty($options['value_hint'])) {
            $view->vars['value_hint'] = $options['value_hint'];
            $view->vars['translatable_value_hint'] = $options['translatable_value_hint'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_config_form_field_type';
    }
}
