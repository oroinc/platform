<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PasswordComplexity extends Constraint
{
    /**
     * @var string Base key of the message, the trans key is constructed by concatenation of the required rule keys
     */
    public $baseKey = 'oro.user.message.invalid_password.';

    public $requireMinLengthKey = 'min_length';

    public $requireUpperCaseKey = 'upper_case';

    public $requireNumbersKey = 'numbers';

    public $requireSpecialCharacterKey = 'special_chars';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_user.validator.password_complexity';
    }
}
