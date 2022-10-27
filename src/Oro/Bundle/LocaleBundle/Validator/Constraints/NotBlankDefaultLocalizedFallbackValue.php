<?php

namespace Oro\Bundle\LocaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation property constraint which could be used for localizable fields,
 * it helps to validate that a localized field has a default value
 */
class NotBlankDefaultLocalizedFallbackValue extends Constraint
{
    /**
     * @var string
     */
    public $errorMessage = 'oro.locale.validators.not_blank_default_localized_value.error_message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_locale.default_localized_fallback_value.not_blank';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
