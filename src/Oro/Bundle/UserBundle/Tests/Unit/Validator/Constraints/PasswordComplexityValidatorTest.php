<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexityValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PasswordComplexityValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new PasswordComplexityValidator(new PasswordComplexityConfigProvider($this->configManager));
    }

    /**
     * @dataProvider validateInvalidDataProvider
     */
    public function testValidateInvalid(array $configMap, string $value, string $message)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap($configMap);

        $constraint = new PasswordComplexity();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($message)
            ->setParameter(
                '{{ length }}',
                $this->configManager->get(PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH)
            )
            ->setInvalidValue($value)
            ->assertRaised();
    }

    /**
     * @dataProvider validateValidDataProvider
     */
    public function testValidateValid(array $configMap, string $value)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap($configMap);

        $constraint = new PasswordComplexity();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * Different rules enabled, invalid value provided
     */
    public function validateInvalidDataProvider(): array
    {
        return [
            'min length' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 10],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => '0',
                'message' => 'oro.user.message.invalid_password.min_length',

            ],
            'upper case' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.upper_case',

            ],
            'lower case' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => '123',
                'message' => 'oro.user.message.invalid_password.lower_case',

            ],
            'numbers' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.numbers',

            ],
            'special chars' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.special_chars',

            ],
            'upper case and numbers' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.upper_case_numbers',

            ],
            'all rules - invalid value' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 10],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.min_length_upper_case_numbers_special_chars',
            ],
            'all rules - invalid length and numbers' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 10],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'paSsword_',
                'message' => 'oro.user.message.invalid_password.min_length_numbers',
            ],
        ];
    }

    public function validateValidDataProvider(): array
    {
        return [
            'all rules disabled' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
            ],
            'min length - valid password' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 8],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'paSsw0rd!',
            ],
            'numbers - valid password' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => '1',
            ],
            'all rules - valid password' => [
                'configMap' => [
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 8],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'paSsw0rd!',
            ],
        ];
    }
}
