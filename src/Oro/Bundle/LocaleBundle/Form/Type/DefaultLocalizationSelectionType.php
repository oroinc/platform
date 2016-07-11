<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

class DefaultLocalizationSelectionType extends LocalizationSelectionType
{
    const NAME = 'oro_locale_default_localization_selection';

    const ENABLED_LOCALIZATIONS_NAME = 'oro_locale___enabled_localizations';
    const DEFAULT_LOCALIZATION_NAME = 'oro_locale___default_localization';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param ConfigManager $configManager
     * @param LocaleSettings $localeSettings
     * @param LocalizationProvider $localizationProvider
     * @param LocalizationChoicesProvider $localizationChoicesProvider
     * @param TranslatorInterface $translator
     * @param RequestStack $requestStack
     */
    public function __construct(
        ConfigManager $configManager,
        LocaleSettings $localeSettings,
        LocalizationProvider $localizationProvider,
        LocalizationChoicesProvider $localizationChoicesProvider,        
        TranslatorInterface $translator,
        RequestStack $requestStack
    ) {
        parent::__construct($configManager, $localeSettings, $localizationProvider, $localizationChoicesProvider);
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $rootForm = $form->getRoot();

        if ($this->isSyncApplicable($rootForm)) {
            $localization = $this->requestStack->getCurrentRequest()->get('localization');
            $defaultLocalization = $this->getDefaultLocalization($localization[self::DEFAULT_LOCALIZATION_NAME]);
            $enabledLocalizations = $this->getEnabledLocalizations($localization[self::ENABLED_LOCALIZATIONS_NAME]);

            if (!in_array($defaultLocalization, $enabledLocalizations, true)) {
                $localization = $this->localizationProvider->getLocalization($defaultLocalization);
                $form->addError(new FormError(
                    $this->translator->trans(
                        'oro.locale.validators.is_not_enabled',
                        ['%localization%' => $localization->getName()],
                        'validators'
                    )
                ));
            }
        }
    }

    /**
     * @param array $defaultLocalizationData
     * @return string
     */
    protected function getDefaultLocalization(array $defaultLocalizationData)
    {
        if (isset($defaultLocalizationData['use_parent_scope_value'])) {
            $systemDefault = $this->localizationProvider->getDefaultLocalization();
            $defaultLocalization = '';
            if ($systemDefault instanceof Localization) {
                $defaultLocalization = $systemDefault->getId();
            }
        } elseif (isset($defaultLocalizationData['value'])) {
            $defaultLocalization = $defaultLocalizationData['value'];
        } else {
            $defaultLocalization = '';
        }

        return $defaultLocalization;
    }

    /**
     * @param array $enabledLocalizationsData
     * @return array
     */
    protected function getEnabledLocalizations(array $enabledLocalizationsData)
    {
        if (isset($enabledLocalizationsData['use_parent_scope_value'])) {
            $enabledLocalizations = $this->localizationChoicesProvider->getLocalizationChoices();
        } elseif (isset($enabledLocalizationsData['value'])) {
            $enabledLocalizations = $enabledLocalizationsData['value'];
        } else {
            $enabledLocalizations = [];
        }

        return $enabledLocalizations;
    }

    /**
     * @param FormInterface $rootForm
     * @return bool
     */
    protected function isSyncApplicable(FormInterface $rootForm)
    {
        return $rootForm && $rootForm->getName() == 'localization' && $rootForm->has(self::ENABLED_LOCALIZATIONS_NAME);
    }
}
