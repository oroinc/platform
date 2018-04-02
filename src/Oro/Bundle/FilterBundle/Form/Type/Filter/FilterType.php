<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class FilterType extends AbstractType
{
    const NAME = 'oro_type_filter';

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
        $result = array('required' => false);
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
        return array_merge(array('required' => false), $options['field_options']);
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $children                     = $form->all();
        if (!is_object($view->vars['value'])) {
            $view->vars['value']['type']  = $children['type']->getViewData();
            $view->vars['value']['value'] = $children['value']->getViewData();
        }
        $view->vars['show_filter']    = $options['show_filter'];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'field_type'       => TextType::class,
                'field_options'    => array(),
                'operator_choices' => array(),
                'operator_type'    => ChoiceType::class,
                'operator_options' => array(),
                'show_filter'      => false,
                'lazy'             => false,
            )
        )->setRequired(
            array(
                'field_type',
                'field_options',
                'operator_choices',
                'operator_type',
                'operator_options',
                'show_filter'
            )
        );
    }
}
