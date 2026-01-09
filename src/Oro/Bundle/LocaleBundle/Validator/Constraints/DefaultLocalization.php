<?php

namespace Oro\Bundle\LocaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating that the default localization is enabled.
 *
 * This constraint ensures that when a default localization is set in the system
 * configuration, it must be included in the list of enabled localizations. It is
 * applied at the class level and uses the {@see DefaultLocalizationValidator}
 * to perform the actual validation logic.
 */
class DefaultLocalization extends Constraint
{
    /**
     * @var string
     */
    public $service = 'oro_locale.default_localization_validator';

    #[\Override]
    public function validatedBy(): string
    {
        return $this->service;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
