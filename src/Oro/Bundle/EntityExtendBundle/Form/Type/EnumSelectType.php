<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * An enum value selector based on 'select2' form type
 */
class EnumSelectType extends AbstractEnumType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $this->disableChoices($view, $options['disabled_values']);
        $this->excludeChoices($view, $options['excluded_values']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $defaultConfigs = [
            'allowClear'  => true,
            'placeholder' => 'oro.form.choose_value'
        ];

        $resolver->setDefaults(
            [
                'empty_value' => null,
                'empty_data'  => null,
                'configs'     => $defaultConfigs,
                'disabled_values' => [],
                'excluded_values' => [],
            ]
        );
        $resolver->setAllowedTypes([
            'disabled_values' => ['array', 'callable'],
            'excluded_values' => ['array', 'callable'],
        ]);
        $resolver->setNormalizers(
            [
                'empty_value' => function (Options $options, $value) {
                    return !$options['expanded'] && !$options['multiple']
                        ? ''
                        : $value;
                },
                // this normalizer allows to add/override config options outside
                'configs' => function (Options $options, $value) use (&$defaultConfigs) {
                    return array_merge($defaultConfigs, $value);
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_translatable_entity';
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
        return 'oro_enum_select';
    }

    /**
     * @param FormView       $view
     * @param array|callable $disabledChoices
     */
    protected function disableChoices(FormView $view, $disabledChoices)
    {
        if (empty($disabledChoices)) {
            return;
        }

        $choices         = $view->vars['choices'];
        array_walk(
            $choices,
            function (ChoiceView $choiceView) use ($disabledChoices) {
                if (is_array($disabledChoices)) {
                    if (in_array($choiceView->value, $disabledChoices)) {
                        $choiceView->attr = array_merge($choiceView->attr, ['disabled' => 'disabled']);
                    }
                } elseif (is_callable($disabledChoices)) {
                    if (!$disabledChoices($choiceView->value)) {
                        $choiceView->attr = array_merge($choiceView->attr, ['disabled' => 'disabled']);
                    }
                }
            }
        );
    }

    /**
     * @param FormView       $view
     * @param array|callable $excludedChoices
     */
    protected function excludeChoices(FormView $view, $excludedChoices)
    {
        if (empty($excludedChoices)) {
            return;
        }

        $view->vars['choices'] = array_filter(
            $view->vars['choices'],
            function (ChoiceView $choiceView) use ($excludedChoices) {
                if (is_array($excludedChoices)) {
                    return !in_array($choiceView->value, $excludedChoices);
                } elseif (is_callable($excludedChoices)) {
                    return $excludedChoices($choiceView->value);
                }
            }
        );
    }
}
