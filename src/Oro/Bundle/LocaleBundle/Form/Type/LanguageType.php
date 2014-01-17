<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Intl\Intl;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;

class LanguageType extends AbstractType
{
    const CONFIG_KEY = 'oro_locale.language';

    /** @var ConfigManager */
    protected $cm;

    public function __construct(ConfigManager $cm)
    {
        $this->cm = $cm;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices'     => $this->getLanguageChoices(),
                'empty_value' => 'Please select...',
            )
        );
    }

    /**
     * @return array
     */
    protected function getLanguageChoices()
    {
        // ensure that default value is always in choice list
        $defaultValue          = $this->cm->get(self::CONFIG_KEY, true);
        $availableTranslations = (array)$this->cm->get(TranslationStatusInterface::CONFIG_KEY);
        $availableTranslations = array_filter(
            $availableTranslations,
            function ($languageStatus) {
                return $languageStatus === TranslationStatusInterface::STATUS_ENABLED;
            }
        );
        $availableLanguages    = array_merge(array_keys($availableTranslations), [$defaultValue]);

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
        return 'oro_language';
    }
}
