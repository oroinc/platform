<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PasswordComplexity extends Constraint
{
    /**
     * @var string Base key of the message, the final trans key should be constructed from the enabled rule keys
     */
    public $baseKey = 'oro.user.message.invalid_password.';

    public $requireMinLengthKey = 'min_length';

    public $requireUpperCaseKey = 'upper_case';

    public $requireNumbersKey = 'numbers';

    public $requireSpecialCharacterKey = 'special_chars';

    /**
     * @var int Known constraint options
     */
    public $requireMinLength;

    public $requireUpperCase;

    public $requireNumbers;

    public $requireSpecialCharacter;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_user.validator.password_complexity';
    }
}
