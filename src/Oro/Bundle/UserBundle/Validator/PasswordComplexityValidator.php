<?php

namespace Oro\Bundle\UserBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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
    const REGEX_UPPER_CASE = '/\p{Lu}/u';
    const REGEX_NUMBERS = '/\pN/u';
    const REGEX_SPECIAL_CHARS = '/[^p{Ll}\p{Lu}\pL\pN]/u';

    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Validates the password field
     *
     * @param string $value
     * @param PasswordComplexity|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        // collect all messages
        $messages = [];
        if (!$this->validMinLength($value)) {
            $messages[] = $constraint->requireMinLengthKey;
        }

        if (!$this->validUpperCase($value)) {
            $messages[] = $constraint->requireUpperCaseKey;
        }

        if (!$this->validNumbers($value)) {
            $messages[] = $constraint->requireNumbersKey;
        }

        if (!$this->validSpecialChars($value)) {
            $messages[] = $constraint->requireSpecialCharacterKey;
        }

        if (count($messages) > 0) {
            // construct an error message translation key
            $message = $constraint->baseKey . join('_', $messages);
            $this->context->buildViolation($message)
                ->setParameters(['{{ length }}' => $this->configManager->get(self::CONFIG_MIN_LENGTH)])
                ->setInvalidValue($value)
                ->addViolation();
        }
    }

    /**
     * Validate minimal length requirement
     *
     * @param $value
     *
     * @return bool
     */
    protected function validMinLength($value)
    {
        return strlen($value) >= (int) $this->configManager->get(self::CONFIG_MIN_LENGTH);
    }

    /**
     * Validate upper case requirement if enabled
     *
     * @param $value
     *
     * @return bool
     */
    protected function validUpperCase($value)
    {
        return !$this->configManager->get(self::CONFIG_UPPER_CASE) || preg_match(self::REGEX_UPPER_CASE, $value);
    }

    /**
     * Validate numbers requirement if enabled
     *
     * @param $value
     *
     * @return bool
     */
    protected function validNumbers($value)
    {
        return !$this->configManager->get(self::CONFIG_NUMBERS) || preg_match(self::REGEX_NUMBERS, $value);
    }

    /**
     * Validate special chars requirement if enabled
     *
     * @param $value
     *
     * @return bool
     */
    protected function validSpecialChars($value)
    {
        return !$this->configManager->get(self::CONFIG_SPECIAL_CHARS) || preg_match(self::REGEX_SPECIAL_CHARS, $value);
    }
}
