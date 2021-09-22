<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Choose fallback type between default and parent values.
 */
class FallbackPropertyType extends AbstractType
{
    const NAME = 'oro_locale_fallback_property';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

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
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'required'           => false,
                'placeholder'        => false,
                'enabled_fallbacks'  => [],
                'existing_fallbacks' => [
                    FallbackType::SYSTEM        => 'oro.locale.fallback.type.default',
                    FallbackType::PARENT_LOCALIZATION => 'oro.locale.fallback.type.parent_localization',
                ],
                'localization' => null,
                'parent_localization' => null,
                'use_tabs' => false,
            ]
        );

        $resolver->setNormalizer(
            'choices',
            function (Options $options, $value) {
                if (!empty($value)) {
                    return $value;
                }

                // system fallback is always enabled
                $enabledFallbacks = array_merge([FallbackType::SYSTEM], $options['enabled_fallbacks']);

                $choices = $options['existing_fallbacks'];
                foreach (array_keys($choices) as $fallback) {
                    if (!in_array($fallback, $enabledFallbacks, true)) {
                        unset($choices[$fallback]);
                    }
                }

                if (array_key_exists(FallbackType::PARENT_LOCALIZATION, $choices) && $options['parent_localization']) {
                    $choices[FallbackType::PARENT_LOCALIZATION] = sprintf(
                        '%s [%s]',
                        $options['parent_localization'],
                        $this->translator->trans((string) $choices[FallbackType::PARENT_LOCALIZATION])
                    );
                }

                if ($options['use_tabs']) {
                    $choices[FallbackType::NONE] = $this->translator->trans('oro.locale.fallback.type.custom');
                }

                return array_flip($choices);
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['localization']) {
            $view->vars['attr']['data-localization'] = $options['localization'];
        }
        if ($options['parent_localization']) {
            $view->vars['attr']['data-parent-localization'] = $options['parent_localization'];
        }
    }
}
