<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PasswordComplexity extends Constraint
{
    public $tooShortMessage = 'oro.user.message.password_min_length';

    public $requireUpperCaseMessage = 'oro.user.message.password_upper_case';

    public $requireNumbersMessage = 'oro.user.message.password_numbers';

    public $requireSpecialCharacterMessage = 'oro.user.message.password_special_chars';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_user.validator.password_complexity';
    }
}
