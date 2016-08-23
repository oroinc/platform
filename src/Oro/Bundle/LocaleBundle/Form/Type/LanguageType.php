<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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
        $availableLanguages = $this->languageProvider->getEnabledLanguages();

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

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $defaultValue = $this->cm->get(self::CONFIG_KEY, true);
        $availableLanguages = $this->languageProvider->getEnabledLanguages();

        if (!in_array($defaultValue, $availableLanguages, true)) {
            if ($form->getParent()->has('use_parent_scope_value')) {
                $form->getParent()->remove('use_parent_scope_value');
                $form->getParent()->add('use_parent_scope_value', 'hidden', ['data' => 0]);
            }
        }
    }
}
