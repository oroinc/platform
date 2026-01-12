<?php

namespace Oro\Bundle\LocaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating localization entities and preventing circular references.
 *
 * This constraint ensures the integrity of the localization hierarchy by detecting
 * and preventing circular parent-child relationships. It is applied at the class
 * level to {@see Localization} entities and uses the {@see LocalizationValidator} to
 * perform validation.
 */
class Localization extends Constraint
{
    /**
     * @var string
     */
    public $messageCircularReference = 'oro.locale.localization.parent.circular_reference';

    /**
     * @var string
     */
    public $service = 'oro_locale.localization_validator';

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
