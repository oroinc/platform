<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides a list of Localizations to choose the necessary localizations in system configuration.
 */
class LocalizationSelectionType extends AbstractType
{
    private const NAME = 'oro_locale_localization_selection';

    /** @var ConfigManager */
    private $configManager;

    /** @var LocalizationManager */
    private $localizationManager;

    /** @var LocalizationChoicesProvider */
    private $localizationChoicesProvider;

    /**
     * @param ConfigManager $configManager
     * @param LocalizationManager $localizationManager
     * @param LocalizationChoicesProvider $localizationChoicesProvider
     */
    public function __construct(
        ConfigManager $configManager,
        LocalizationManager $localizationManager,
        LocalizationChoicesProvider $localizationChoicesProvider
    ) {
        $this->configManager = $configManager;
        $this->localizationManager = $localizationManager;
        $this->localizationChoicesProvider = $localizationChoicesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->getLocalizationChoices(true),
            'show_all' => false,
            'placeholder' => '',
            'translatable_options' => false,
            'configs' => [
                'placeholder' => 'oro.locale.localization.form.placeholder.select_localization',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $ids = array_values($this->getLocalizationChoices($options['show_all']));

        $view->vars['choices'] = array_filter(
            $view->vars['choices'],
            function (ChoiceView $choiceView) use ($ids) {
                return in_array($choiceView->data, $ids, true);
            }
        );
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
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroChoiceType::class;
    }

    /**
     * @param bool $showAll
     * @return array
     */
    private function getLocalizationChoices($showAll = false): array
    {
        $availableLocalizations = $this->localizationChoicesProvider->getLocalizationChoices();

        if (!$showAll) {
            $availableLocalizations = array_flip(
                array_intersect_key(
                    array_flip($availableLocalizations),
                    $this->getEnabledLocalizations()
                )
            );
        }

        return $availableLocalizations;
    }

    /**
     * @return array
     */
    private function getEnabledLocalizations(): array
    {
        $enabledLocalizationIds = (array)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS)
        );

        return $this->localizationManager->getLocalizations($enabledLocalizationIds);
    }
}
