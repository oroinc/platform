<?php

namespace Oro\Bundle\UserBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;

/**
 * Validates a password against requirements from the constraint or stored in the system config (if not set)
 */
class PasswordComplexityValidator extends ConstraintValidator
{
    const REGEX_LOWER_CASE = '/\p{Ll}/u';
    const REGEX_UPPER_CASE = '/\p{Lu}/u';
    const REGEX_NUMBERS = '/\p{N}/u';
    const REGEX_SPECIAL_CHARS = '/[\s!-\/:-@\[-`{|}~]/u'; // !"#$%&'()*+,-./:;<=>?@[\]^_`{|}~ + spacing

    /** @var PasswordComplexityConfigProvider */
    private $configProvider;

    public function __construct(PasswordComplexityConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof PasswordComplexity) {
            throw new UnexpectedTypeException($constraint, PasswordComplexity::class);
        }

        if (empty($value)) {
            return;
        }

        // collect all messages
        $messages = [];
        if (!$this->validMinLength($value, $constraint)) {
            $messages[] = $constraint->requireMinLengthKey;
        }

        if (!$this->validLowerCase($value, $constraint)) {
            $messages[] = $constraint->requireLowerCaseKey;
        }

        if (!$this->validUpperCase($value, $constraint)) {
            $messages[] = $constraint->requireUpperCaseKey;
        }

        if (!$this->validNumbers($value, $constraint)) {
            $messages[] = $constraint->requireNumbersKey;
        }

        if (!$this->validSpecialChars($value, $constraint)) {
            $messages[] = $constraint->requireSpecialCharacterKey;
        }

        if (count($messages) > 0) {
            // construct an error message translation key
            $message = $constraint->baseKey . join('_', $messages);
            $this->context->buildViolation($message)
                ->setParameters(['{{ length }}' => (int) $this->configProvider->getMinLength()])
                ->setInvalidValue($value)
                ->addViolation();
        }
    }

    /**
     * Get configured minimal length
     *
     * @param PasswordComplexity $constraint
     *
     * @return int
     */
    protected function getMinLength(PasswordComplexity $constraint)
    {
        return null === $constraint->requireMinLength
            ? (int) $this->configProvider->getMinLength()
            : (int) $constraint->requireMinLength;
    }

    /**
     * Validate minimal length requirement
     *
     * @param $value
     * @param PasswordComplexity $constraint
     *
     * @return bool
     */
    protected function validMinLength($value, PasswordComplexity $constraint)
    {
        return mb_strlen($value) >= $this->getMinLength($constraint);
    }

    /**
     * Validate upper case requirement if enabled
     *
     * @param $value
     * @param PasswordComplexity $constraint
     *
     * @return bool
     */
    protected function validUpperCase($value, PasswordComplexity $constraint)
    {
        $isEnabled = null === $constraint->requireUpperCase
            ? $this->configProvider->getUpperCase()
            : $constraint->requireUpperCase;

        return !$isEnabled || preg_match(self::REGEX_UPPER_CASE, $value);
    }

    /**
     * Validate lower case requirement if enabled
     *
     * @param $value
     * @param PasswordComplexity $constraint
     *
     * @return bool
     */
    protected function validLowerCase($value, PasswordComplexity $constraint)
    {
        $isEnabled = null === $constraint->requireLowerCase
            ? $this->configProvider->getLowerCase()
            : $constraint->requireLowerCase;

        return !$isEnabled || preg_match(self::REGEX_LOWER_CASE, $value);
    }

    /**
     * Validate numbers requirement if enabled
     *
     * @param $value
     * @param PasswordComplexity $constraint
     *
     * @return bool
     */
    protected function validNumbers($value, PasswordComplexity $constraint)
    {
        $isEnabled = null === $constraint->requireNumbers
            ? $this->configProvider->getNumbers()
            : $constraint->requireNumbers;

        return !$isEnabled || preg_match(self::REGEX_NUMBERS, $value);
    }

    /**
     * Validate special chars requirement if enabled
     *
     * @param $value
     * @param PasswordComplexity $constraint
     *
     * @return bool
     */
    protected function validSpecialChars($value, PasswordComplexity $constraint)
    {
        $isEnabled = null === $constraint->requireSpecialCharacter
            ? $this->configProvider->getSpecialChars()
            : $constraint->requireSpecialCharacter;

        return !$isEnabled || preg_match(self::REGEX_SPECIAL_CHARS, $value);
    }
}
