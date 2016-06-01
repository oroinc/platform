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

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
