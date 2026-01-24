<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting formatting options based on available localizations.
 *
 * This form type provides a dropdown selector for choosing formatting preferences
 * (such as date/time or number formats) from the available localizations configured
 * in the system. It extends {@see OroChoiceType} to provide autocomplete functionality and
 * is typically used in system configuration forms.
 */
class FormattingSelectType extends AbstractType
{
    const NAME = 'oro_formatting_select';

    /**
     * @var LocalizationChoicesProvider
     */
    private $provider;

    public function __construct(LocalizationChoicesProvider $provider)
    {
        $this->provider = $provider;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'placeholder' => '',
            'choices' => $this->provider->getFormattingChoices(),
            'translatable_options' => false,
            'configs' => [
                'placeholder' => 'oro.locale.localization.form.placeholder.select_formatting'
            ],
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
