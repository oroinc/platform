<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;

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
                $this->checkOptions($options);

                if ($options['full_localization_list']) {
                    return $this->localizationChoicesProvider->getLocalizationChoices();
                }

                $localizations = $options['localizations_list'];
                if (!count($localizations)) {
                    $localizations = $this->getLocalizations();
                }

                $localizations = array_merge($localizations, (array)$options['additional_localizations']);
                $localizations = $this->checkLocalizations($localizations);

                return $this->getChoices($localizations, $options['compact']);
            },
            'compact' => false,
            'localizations_list' => null,
            'additional_localizations' => null,
            'full_localization_list' => false
        ]);
    }

    /**
     * @param Options $options
     * @throws LogicException
     */
    protected function checkOptions(Options $options)
    {
        if (($options['localizations_list'] !== null && !is_array($options['localizations_list']))
            || (is_array($options['localizations_list']) && empty($options['localizations_list']))
        ) {
            throw new LogicException('The option "localizations_list" must be null or not empty array.');
        }

        if ($options['additional_localizations'] !== null && !is_array($options['additional_localizations'])) {
            throw new LogicException('The option "additional_localizations" must be null or array.');
        }
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
        foreach ($localizations as $id => $label) {
            $localization = $this->localizationManager->getLocalization($id);
            if (!($localization instanceof Localization)) {
                unset($localizations[$id]);
            }
        }

        return $localizations;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return array
     */
    protected function getLocalizations()
    {
        return $this->localizationChoicesProvider->getLocalizationChoices();
    }
}
