<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\DataTransformer\MultipleValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides a functional for building all localized fields.
 */
class LocalizedPropertyType extends AbstractType
{
    public const NAME = 'oro_locale_localized_property';

    public const FIELD_DEFAULT = 'default';
    public const FIELD_LOCALIZATIONS = 'localizations';

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formType    = $options['entry_type'];
        $formOptions = $options['entry_options'];
        $excludeParentLocalization = $options['exclude_parent_localization'];

        $builder
            ->add(
                self::FIELD_DEFAULT,
                $formType,
                array_merge($formOptions, ['label' => 'oro.locale.fallback.value.default'])
            )
            ->add(self::FIELD_LOCALIZATIONS, LocalizationCollectionType::class, [
                'entry_type' => $formType,
                'entry_options' => $formOptions,
                'exclude_parent_localization' => $excludeParentLocalization,
                'use_tabs' => $options['use_tabs'],
            ]);

        $builder->addViewTransformer(new MultipleValueTransformer(self::FIELD_DEFAULT, self::FIELD_LOCALIZATIONS));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'entry_type',
        ]);

        $resolver->setDefaults([
            'entry_options' => [],
            'exclude_parent_localization' => false,
            'use_tabs' => false,
        ]);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['use_tabs']) {
            array_splice($view->vars['block_prefixes'], -1, 0, [$this->getBlockPrefix() . '_tabs']);
        }
    }
}
