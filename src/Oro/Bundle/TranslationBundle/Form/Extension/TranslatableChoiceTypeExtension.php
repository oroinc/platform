<?php

namespace Oro\Bundle\TranslationBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extends the choice form type with "translatable_options" and "translatable_groups" options
 * used to manage the translation of rendered choice items and groups.
 */
class TranslatableChoiceTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('translatable_groups', true)
            ->setDefault('translatable_options', true);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!$options['translatable_groups']) {
            $view->vars['translatable_groups'] = false;
        }
        if (!$options['translatable_options']) {
            $view->vars['translatable_options'] = false;
        }
    }
}
