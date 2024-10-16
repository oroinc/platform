<?php

namespace Oro\Bundle\LocaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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
