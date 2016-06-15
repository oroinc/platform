<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Model\FallbackType;

class FallbackPropertyType extends AbstractType
{
    const NAME = 'oro_locale_fallback_property';

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
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'required'           => false,
                'empty_value'        => false,
                'enabled_fallbacks'  => [],
                'existing_fallbacks' => [
                    FallbackType::SYSTEM        => 'oro.locale.fallback.type.default',
                    FallbackType::PARENT_LOCALIZATION => 'oro.locale.fallback.type.parent_localization',
                ],
                'localization' => null,
                'parent_localization' => null,
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
                        $this->translator->trans($choices[FallbackType::PARENT_LOCALIZATION])
                    );
                }

                return $choices;
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
            $view->vars['attr']['test-localization'] = $options['localization'];
        }
        if ($options['parent_localization']) {
            $view->vars['attr']['data-parent-localization'] = $options['parent_localization'];
            $view->vars['attr']['test-parent-localization'] = $options['parent_localization'];
        }
    }
}
