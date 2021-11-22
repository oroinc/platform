<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that a password against requirements from the constraint
 * or stored in the system config (if not set).
 */
class PasswordComplexity extends Constraint
{
    /**
     * @var string Base key of the message, the final trans key should be constructed from the enabled rule keys
     */
    public $baseKey = 'oro.user.message.invalid_password.';

    public $requireMinLengthKey = 'min_length';

    public $requireLowerCaseKey = 'lower_case';

    public $requireUpperCaseKey = 'upper_case';

    public $requireNumbersKey = 'numbers';

    public $requireSpecialCharacterKey = 'special_chars';

    /**
     * @var int Known constraint options
     */
    public $requireMinLength;

    public $requireLowerCase;

    public $requireUpperCase;

    public $requireNumbers;

    public $requireSpecialCharacter;
}
