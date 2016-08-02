<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\AbstractType;

class DictionaryFilterType extends AbstractType
{
    const NAME = 'oro_type_dictionary_filter';
    const TYPE_IN = 1;
    const TYPE_NOT_IN = 2;
    const EQUAL = 3;
    const NOT_EQUAL = 4;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', $options['operator_type'], $this->createOperatorOptions($options));
        $builder->add('value', $options['field_type'], $this->createFieldOptions($options));
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function createOperatorOptions(array $options)
    {
        $result = ['required' => false];
        if ($options['operator_choices']) {
            $result['choices'] = $options['operator_choices'];
        }
        $result = array_merge($result, $options['operator_options']);

        return $result;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function createFieldOptions(array $options)
    {
        return array_merge(['required' => false], $options['field_options']);
    }

    /**
     * {@inheritDoc}
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

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $children = $form->all();
        $view->vars['value']['type'] = $children['type']->getViewData();
        $view->vars['value']['value'] = $children['value']->getViewData();
        $view->vars['show_filter'] = $options['show_filter'];
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'field_type' => 'text',
                'field_options' => [],
                'operator_type' => 'choice',
                'operator_options' => [],
                'show_filter' => false,
                'populate_default' => false,
                'default_value' => null,
                'null_value' => null,
                'class' => '',
                'operator_choices' => [
                    self::TYPE_IN => $this->translator->trans('oro.filter.form.label_type_in'),
                    self::TYPE_NOT_IN => $this->translator->trans('oro.filter.form.label_type_not_in'),
                ],
            ]
        )->setRequired(
            [
                'field_type',
                'field_options',
                'operator_choices',
                'operator_type',
                'operator_options',
                'show_filter'
            ]
        );
    }
}
