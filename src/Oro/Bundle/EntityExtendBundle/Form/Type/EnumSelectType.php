<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An enum value selector based on 'select2' form type
 */
class EnumSelectType extends AbstractEnumType
{
    const NAME = 'oro_enum_select';

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $this->disableChoices($view, $options['disabled_values']);
        $this->excludeChoices($view, $options['excluded_values']);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $defaultConfigs = [
            'allowClear' => true,
            'placeholder' => 'oro.form.choose_value'
        ];

        $resolver->setDefaults(
            [
                'placeholder' => null,
                'empty_data' => static fn (Options $options) => $options['multiple'] ? [] : null,
                'configs' => $defaultConfigs,
                'disabled_values' => [],
                'excluded_values' => [],
                'choice_translation_domain' => false, // Enums should use the Gedmo for translations
            ]
        );

        $resolver->setAllowedTypes('disabled_values', ['array', 'callable']);
        $resolver->setAllowedTypes('excluded_values', ['array', 'callable']);

        $resolver->setNormalizer(
            'placeholder',
            function (Options $options, $value) {
                return (null === $value) && !$options['expanded'] && !$options['multiple']
                    ? ''
                    : $value;
            }
        );

        // this normalizer allows to add/override config options outside
        $resolver->setNormalizer(
            'configs',
            function (Options $options, $value) use (&$defaultConfigs) {
                return array_merge($defaultConfigs, $value);
            }
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2TranslatableEntityType::class;
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    /**
     * @param FormView $view
     * @param array|callable $disabledChoices
     */
    protected function disableChoices(FormView $view, $disabledChoices)
    {
        if (empty($disabledChoices)) {
            return;
        }

        $choices = $view->vars['choices'];
        array_walk(
            $choices,
            function (ChoiceView $choiceView) use ($disabledChoices) {
                if (is_array($disabledChoices)) {
                    if (in_array($choiceView->value, $disabledChoices, true)) {
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

    protected function excludeChoices(FormView $view, array|callable $excludedChoices): void
    {
        if (empty($excludedChoices)) {
            return;
        }

        $view->vars['choices'] = array_filter(
            $view->vars['choices'],
            function (ChoiceView $choiceView) use ($excludedChoices) {
                if (is_array($excludedChoices)) {
                    return !in_array($choiceView->data->getInternalId(), $excludedChoices);
                } elseif (is_callable($excludedChoices)) {
                    return $excludedChoices($choiceView->data->getInternalId());
                }
            }
        );
    }
}
