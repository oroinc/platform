<?php

namespace Oro\Bundle\UserBundle\Validator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;

/**
 * Validates a password against requirements stored in the system config
 */
class PasswordComplexityValidator extends ConstraintValidator
{
    const CONFIG_MIN_LENGTH = 'oro_user.password_min_length';
    const CONFIG_UPPER_CASE = 'oro_user.password_upper_case';
    const CONFIG_NUMBERS = 'oro_user.password_numbers';
    const CONFIG_SPECIAL_CHARS = 'oro_user.password_special_chars';
    const REGEX_UPPER_CASE = '/\p{Lu}/';
    const REGEX_NUMBERS = '/\pN/';
    const REGEX_SPECIAL_CHARS = '/[^p{Ll}\p{Lu}\pL\pN]/';

    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param string $value
     * @param PasswordComplexity|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        $minLength = $this->configManager->get(self::CONFIG_MIN_LENGTH);

        if ($minLength > 0 && strlen($value) < $minLength) {
            $this->context->buildViolation($constraint->tooShortMessage)
                ->setParameters(['{{ length }}' => $minLength])
                ->setInvalidValue($value)
                ->addViolation();
        }

        if ($this->configManager->get(self::CONFIG_UPPER_CASE) && !preg_match(self::REGEX_UPPER_CASE, $value)) {
            $this->context->buildViolation($constraint->requireUpperCaseMessage)
                ->setInvalidValue($value)
                ->addViolation();
        }

        if ($this->configManager->get(self::CONFIG_NUMBERS) && !preg_match(self::REGEX_NUMBERS, $value)) {
            $this->context->buildViolation($constraint->requireNumbersMessage)
                ->setInvalidValue($value)
                ->addViolation();
        }

        if ($this->configManager->get(self::CONFIG_SPECIAL_CHARS) && !preg_match(self::REGEX_SPECIAL_CHARS, $value)) {
            $this->context->buildViolation($constraint->requireSpecialCharacterMessage)
                ->setInvalidValue($value)
                ->addViolation();
        }
    }
}
