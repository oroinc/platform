<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageType extends AbstractType
{
    const CONFIG_KEY = 'oro_locale.language';

    /** @var ConfigManager */
    protected $cm;

    /** @var LanguageProvider */
    protected $languageProvider;

    /**
     * @param ConfigManager $cm
     * @param LanguageProvider $languageProvider
     */
    public function __construct(ConfigManager $cm, LanguageProvider $languageProvider)
    {
        $this->cm = $cm;
        $this->languageProvider = $languageProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => $this->getLanguageChoices(true),
                'show_all' => false,
                'placeholder' => '',
                'translatable_options' => false,
                'configs' => [
                    'placeholder' => 'oro.locale.localization.form.placeholder.select_language',
                ],
            ]
        );
    }

    /**
     * @param bool $showAll
     * @return array
     */
    protected function getLanguageChoices($showAll = false)
    {
        // ensure that default value is always in choice list
        $defaultValue = $this->cm->get(self::CONFIG_KEY, true);

        if ($showAll) {
            $availableLanguages = array_merge($this->languageProvider->getEnabledLanguages(), [$defaultValue]);
        } else {
            $availableLanguages = (array)$this->cm->get(Configuration::getConfigKeyByName('languages'));
        }

        $allLanguages = Intl::getLocaleBundle()->getLocaleNames($defaultValue);

        return array_flip(array_intersect_key($allLanguages, array_flip($availableLanguages)));
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $codes = array_values($this->getLanguageChoices($options['show_all']));

        $view->vars['choices'] = array_filter(
            $view->vars['choices'],
            function (ChoiceView $choiceView) use ($codes) {
                return in_array($choiceView->data, $codes, true);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroChoiceType::class;
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
        return 'oro_language';
    }
}
