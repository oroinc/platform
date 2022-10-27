<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates a password against requirements from the constraint or stored in the system config (if not set)
 */
class PasswordComplexityValidator extends ConstraintValidator
{
    private const REGEX_LOWER_CASE = '/\p{Ll}/u';
    private const REGEX_UPPER_CASE = '/\p{Lu}/u';
    private const REGEX_NUMBERS = '/\p{N}/u';
    private const REGEX_SPECIAL_CHARS = '/[\s!-\/:-@\[-`{|}~]/u'; // !"#$%&'()*+,-./:;<=>?@[\]^_`{|}~ + spacing

    /** Complexity rules to check the password validity (in order) and respective trans keys */
    protected static array $rulesMap = [
        'validMinLength'        => 'requireMinLengthKey',
        'validLowerCase'        => 'requireLowerCaseKey',
        'validUpperCase'        => 'requireUpperCaseKey',
        'validNumbers'          => 'requireNumbersKey',
        'validSpecialCharacter' => 'requireSpecialCharacterKey',
    ];

    private PasswordComplexityConfigProvider $configProvider;

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

        if (null === $value || '' === $value) {
            return;
        }

        $messages = [];
        // execute rule validators and collect all messages
        foreach (self::$rulesMap as $method => $transKey) {
            if (!$this->$method($value, $constraint)) {
                $messages[] = $constraint->$transKey;
            }
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
     * Gets configured minimal length.
     */
    protected function getMinLength(PasswordComplexity $constraint): int
    {
        return null === $constraint->requireMinLength
            ? (int) $this->configProvider->getMinLength()
            : (int) $constraint->requireMinLength;
    }

    /**
     * Validated minimal length requirement.
     */
    protected function validMinLength($value, PasswordComplexity $constraint): bool
    {
        return mb_strlen($value) >= $this->getMinLength($constraint);
    }

    /**
     * Validates upper case requirement if enabled.
     */
    protected function validUpperCase($value, PasswordComplexity $constraint): bool
    {
        $isEnabled = null === $constraint->requireUpperCase
            ? $this->configProvider->getUpperCase()
            : $constraint->requireUpperCase;

        return !$isEnabled || preg_match(self::REGEX_UPPER_CASE, $value);
    }

    /**
     * Validates lower case requirement if enabled.
     */
    protected function validLowerCase($value, PasswordComplexity $constraint): bool
    {
        $isEnabled = null === $constraint->requireLowerCase
            ? $this->configProvider->getLowerCase()
            : $constraint->requireLowerCase;

        return !$isEnabled || preg_match(self::REGEX_LOWER_CASE, $value);
    }

    /**
     * Validates numbers requirement if enabled.
     */
    protected function validNumbers($value, PasswordComplexity $constraint): bool
    {
        $isEnabled = null === $constraint->requireNumbers
            ? $this->configProvider->getNumbers()
            : $constraint->requireNumbers;

        return !$isEnabled || preg_match(self::REGEX_NUMBERS, $value);
    }

    /**
     * Validates special chars requirement if enabled.
     */
    protected function validSpecialCharacter($value, PasswordComplexity $constraint): bool
    {
        $isEnabled = null === $constraint->requireSpecialCharacter
            ? $this->configProvider->getSpecialChars()
            : $constraint->requireSpecialCharacter;

        return !$isEnabled || preg_match(self::REGEX_SPECIAL_CHARS, $value);
    }
}
