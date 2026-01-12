<?php

namespace Oro\Bundle\TranslationBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type for filtering languages in datagrids.
 *
 * Extends the entity filter type to provide language-specific filtering capabilities.
 * Formats language codes using the LanguageCodeFormatter and sorts the available language
 * choices alphabetically for improved user experience in filter dropdowns.
 */
class LanguageFilterType extends AbstractType
{
    public const NAME = 'oro_translation_filter_language';

    /** @var LanguageCodeFormatter */
    protected $formatter;

    public function __construct(LanguageCodeFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EntityFilterType::class;
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        foreach ($view->children['value']->vars['choices'] as $choiceView) {
            $choiceView->label = $this->formatter->formatLocale($choiceView->label);
        }

        usort($view->children['value']->vars['choices'], function (ChoiceView $a, ChoiceView $b) {
            return strnatcmp($a->label, $b->label);
        });
    }
}
