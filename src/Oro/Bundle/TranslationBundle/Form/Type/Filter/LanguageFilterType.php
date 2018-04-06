<?php

namespace Oro\Bundle\TranslationBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class LanguageFilterType extends AbstractType
{
    const NAME = 'oro_translation_filter_language';

    /** @var LanguageCodeFormatter */
    protected $formatter;

    /**
     * @param LanguageCodeFormatter $formatter
     */
    public function __construct(LanguageCodeFormatter $formatter)
    {
        $this->formatter = $formatter;
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
    public function getParent()
    {
        return EntityFilterType::class;
    }

    /**
     * {@inheritdoc}
     */
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
