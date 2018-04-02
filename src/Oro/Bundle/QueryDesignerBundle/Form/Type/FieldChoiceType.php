<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class FieldChoiceType extends AbstractType
{
    const NAME = 'oro_field_choice';

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $componentOptions = $options['page_component_options'];
        $componentOptions['select2']['placeholder'] = $this->translator->trans($options['placeholder']);

        if (isset($options['include_fields'])) {
            $componentOptions['include'] = $options['include_fields'];
        }

        if (isset($options['exclude_fields'])) {
            $componentOptions['exclude'] = $options['exclude_fields'];
        }

        if (isset($options['filter_preset'])) {
            $componentOptions['filterPreset'] = $options['filter_preset'];
        }

        $view->vars['page_component_options'] = $componentOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('page_component_name');
        $resolver->setRequired(['page_component', 'page_component_options']);
        $resolver->setAllowedTypes('page_component', 'string');
        $resolver->setAllowedTypes('page_component_name', 'string');
        $resolver->setAllowedTypes('page_component_options', 'array');

        $resolver->setDefaults([
            'placeholder'            => 'oro.entity.form.choose_entity_field',
            'exclude_fields'         => [],
            'include_fields'         => [],
            'filter_preset'          => 'querydesigner',
            'page_component'         => 'oroui/js/app/components/view-component',
            'page_component_options' => [
                'view'         => 'oroentity/js/app/views/field-choice-view',
                'autoRender'   => true,
                'select2'      => ['dropdownAutoWidth' => true, 'pageableResults' => true],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($options['page_component_name'])) {
            $view->vars['attr']['data-page-component-name'] = $options['page_component_name'];
        }
        $view->vars['attr']['data-page-component-module'] = $options['page_component'];
        $view->vars['attr']['data-page-component-options'] = json_encode($view->vars['page_component_options']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return HiddenType::class;
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
        return self::NAME;
    }
}
