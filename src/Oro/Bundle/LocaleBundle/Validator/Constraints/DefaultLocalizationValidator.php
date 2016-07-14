<?php

namespace Oro\Bundle\LocaleBundle\Validator\Constraints;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

class DefaultLocalizationValidator extends ConstraintValidator
{
    const ENABLED_LOCALIZATIONS_NAME = 'oro_locale___enabled_localizations';

    /**
     * @var LocalizationProvider
     */
    protected $localizationProvider;

    /**
     * @param LocalizationProvider $localizationProvider
     */
    public function __construct(LocalizationProvider $localizationProvider)
    {
        $this->localizationProvider = $localizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($defaultLocalization, Constraint $constraint)
    {
        $rootForm = $this->context->getRoot();

        if (!$this->isSyncApplicable($rootForm)) {
            return;
        }

        $localizationData = $rootForm->getData();

        $enabledLocalizations = $this->getEnabledLocalizations($localizationData[self::ENABLED_LOCALIZATIONS_NAME]);

        if (in_array($defaultLocalization, $enabledLocalizations, true)) {
            return;
        }

        $localization = $this->localizationProvider->getLocalization((int)$defaultLocalization);
        if ($localization instanceof Localization) {
            $message = 'oro.locale.validators.is_not_enabled';
            $params = ['%localization%' => $localization->getName()];
        } else {
            $message =  'oro.locale.validators.unknown_localization';
            $params = ['%localization_id%' => $defaultLocalization];
        }

        $this->context->buildViolation($message, $params)
            ->addViolation();
    }

    /**
     * @param array $enabledLocalizationsData
     * @return array
     */
    protected function getEnabledLocalizations(array $enabledLocalizationsData)
    {
        return isset($enabledLocalizationsData['value']) ? $enabledLocalizationsData['value'] : [];
    }

    /**
     * @param FormInterface $rootForm
     * @return bool
     */
    protected function isSyncApplicable(FormInterface $rootForm)
    {
        return $rootForm->getName() == 'localization' && $rootForm->has(self::ENABLED_LOCALIZATIONS_NAME);
    }
}
