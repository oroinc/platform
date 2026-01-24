<?php

namespace Oro\Bundle\LocaleBundle\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator for the {@see DefaultLocalization} constraint.
 *
 * This validator checks that the selected default localization is included in
 * the list of enabled localizations. It operates on localization configuration
 * forms and ensures data consistency by preventing the selection of a default
 * localization that is not enabled. If validation fails, it provides localized
 * error messages indicating either that the localization is not enabled or that
 * the localization ID is unknown.
 */
class DefaultLocalizationValidator extends ConstraintValidator
{
    const ENABLED_LOCALIZATIONS_NAME = 'oro_locale___enabled_localizations';

    /**
     * @var LocalizationManager
     */
    protected $localizationManager;

    public function __construct(LocalizationManager $localizationManager)
    {
        $this->localizationManager = $localizationManager;
    }

    #[\Override]
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

        $localization = $this->localizationManager->getLocalization((int)$defaultLocalization);
        if ($localization instanceof Localization) {
            $message = 'oro.locale.validators.is_not_enabled';
            $params = ['%localization%' => $localization->getName()];
        } else {
            $message =  'oro.locale.validators.unknown_localization';
            $params = ['%localization_id%' => $defaultLocalization];
        }

        $this->context->buildViolation($message, $params)->addViolation();
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
        return $rootForm->getName() === 'localization' && $rootForm->has(self::ENABLED_LOCALIZATIONS_NAME);
    }
}
