<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Intl\Intl;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

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
                'choices'     => array_flip($this->getLanguageChoices()),
                'choices_as_values' => true,
                'empty_value' => 'Please select...',
            ]
        );
    }

    /**
     * @return array
     */
    protected function getLanguageChoices()
    {
        // ensure that default value is always in choice list
        $defaultValue = $this->cm->get(self::CONFIG_KEY, true);
        $availableLanguages = array_merge($this->languageProvider->getEnabledLanguages(), [$defaultValue]);

        $allLanguages = Intl::getLocaleBundle()->getLocaleNames('en');

        return array_intersect_key($allLanguages, array_flip($availableLanguages));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'locale';
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
