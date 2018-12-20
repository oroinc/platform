<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizationSelectionType extends AbstractType
{
    const NAME = 'oro_locale_localization_selection';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var LocalizationManager
     */
    protected $localizationManager;

    /**
     * @var LocalizationChoicesProvider
     */
    protected $localizationChoicesProvider;

    /**
     * @var string
     */
    protected $localizationSelectorConfigKey;

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }

    /**
     * @param ConfigManager $configManager
     * @param LocaleSettings $localeSettings
     * @param LocalizationManager $localizationManager
     * @param LocalizationChoicesProvider $localizationChoicesProvider
     */
    public function __construct(
        ConfigManager $configManager,
        LocaleSettings $localeSettings,
        LocalizationManager $localizationManager,
        LocalizationChoicesProvider $localizationChoicesProvider
    ) {
        $this->configManager = $configManager;
        $this->localeSettings = $localeSettings;
        $this->localizationManager = $localizationManager;
        $this->localizationChoicesProvider = $localizationChoicesProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                $localizations = $this->getLocalizations();

                if ($options['full_localization_list']) {
                    return $localizations;
                }

                $localizations = $this->checkLocalizations($localizations);

                return $this->getChoices($localizations, $options['compact']);
            },
            'compact' => false,
            'full_localization_list' => false,
            'placeholder' => '',
            'translatable_options' => false,
            'configs' => [
                'placeholder' => 'oro.locale.localization.form.placeholder.select_localization',
            ],
        ]);
    }

    /**
     * @param array $localizations
     * @param boolean $isCompact
     * @return array
     */
    protected function getChoices(array $localizations, $isCompact)
    {
        if ($isCompact) {
            $choices = array_combine($localizations, $localizations);
        } else {
            $localizationChoices = $this->localizationChoicesProvider->getLocalizationChoices();
            $choices = array_intersect_key($localizationChoices, $localizations);
        }

        return $choices;
    }

    /**
     * @param array $localizations
     *
     * @return array
     */
    protected function checkLocalizations(array $localizations)
    {
        foreach ($localizations as $label => $id) {
            $localization = $this->localizationManager->getLocalization($id);
            if (!($localization instanceof Localization)) {
                unset($localizations[$label]);
            }
        }

        return $localizations;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return OroChoiceType::class;
    }

    /**
     * @return array
     */
    protected function getLocalizations()
    {
        return $this->localizationChoicesProvider->getLocalizationChoices();
    }
}
