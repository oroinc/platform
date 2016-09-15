<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;
use Oro\Bundle\UserBundle\Validator\PasswordComplexityValidator;

class PasswordComplexityValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConstraintViolationBuilderInterface */
    protected $violationBuilder;

    /** @var PasswordComplexity */
    protected $constraint;

    protected function setUp()
    {
        $this->violationBuilder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->violationBuilder->method('setInvalidValue')->willReturnSelf();
        $this->violationBuilder->method('setParameters')->willReturnSelf();
        $this->violationBuilder->method('addViolation')->willReturnSelf();

        $this->constraint = new PasswordComplexity([]);
    }

    /**
     * @dataProvider validateInvalidDataProvider
     *
     * @param array $configMap System config
     * @param string $value    Testing password
     * @param string $message  Expected message param
     */
    public function testValidateInvalid(array $configMap, $value, $message)
    {
        $validator = new PasswordComplexityValidator($this->getConfigManager($configMap));
        $context = $this->getMockBuilder(ExecutionContextInterface::class)->disableOriginalConstructor()->getMock();

        $context->expects($this->once())
            ->method('buildViolation')
            ->with($message)
            ->willReturn($this->violationBuilder);

        /** @var ExecutionContext $context */
        $validator->initialize($context);
        $validator->validate($value, $this->constraint);
    }

    /**
     * @dataProvider validateValidDataProvider
     *
     * @param array $configMap System config
     * @param string $value    Testing password
     */
    public function testValidateValid(array $configMap, $value)
    {
        $validator = new PasswordComplexityValidator($this->getConfigManager($configMap));
        $context = $this->getMockBuilder(ExecutionContextInterface::class)->disableOriginalConstructor()->getMock();

        $context->expects($this->never())
            ->method('buildViolation');

        /** @var ExecutionContext $context */
        $validator->initialize($context);
        $validator->validate($value, $this->constraint);
    }

    /**
     * Different rules enabled, invalid value provided
     *
     * @return array
     */
    public function validateInvalidDataProvider()
    {
        return [
            'min length' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 10],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.min_length',

            ],
            'upper case' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.upper_case',

            ],
            'numbers' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.numbers',

            ],
            'special chars' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.special_chars',

            ],
            'upper case and numbers' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.upper_case_numbers',

            ],
            'all rules - invalid value' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 10],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'password',
                'message' => 'oro.user.message.invalid_password.min_length_upper_case_numbers_special_chars',
            ],
            'all rules - invalid length and numbers' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 10],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'paSsword!',
                'message' => 'oro.user.message.invalid_password.min_length_numbers',
            ],
        ];
    }

    /**
     * @return array
     */
    public function validateValidDataProvider()
    {
        return [
            'all rules disabled' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
            ],
            'min length - valid password' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 8],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'paSsw0rd!',
            ],
            'numbers - valid password' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => '1',
            ],
            'all rules - valid password' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 8],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'paSsw0rd!',
            ],
        ];
    }

    /**
     * @param array $configMap
     *
     * @return ConfigManager
     */
    protected function getConfigManager(array $configMap)
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->method('get')->willReturnMap($configMap);

        /** @var ConfigManager $configManager */
        return $configManager;
    }

    protected function tearDown()
    {
        unset($this->violationBuilder, $this->constraint);
    }
}
