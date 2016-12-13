<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
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
        $validator = new PasswordComplexityValidator($this->getConfigProvider($configMap));
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
        $validator = new PasswordComplexityValidator($this->getConfigProvider($configMap));
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
                    [PasswordComplexityConfigProvider::CONFIG_MIN_LENGTH, false, false, null, 10],
                    [PasswordComplexityConfigProvider::CONFIG_LOWER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
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

    /**
     * @return array
     */
    public function validateValidDataProvider()
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

    /**
     * @param array $configMap
     *
     * @return PasswordComplexityConfigProvider
     */
    protected function getConfigProvider(array $configMap)
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->method('get')->willReturnMap($configMap);

        /** @var ConfigManager $configManager */
        $configProvider = new PasswordComplexityConfigProvider($configManager);

        /** @var PasswordComplexityConfigProvider $configManager */
        return $configProvider;
    }

    protected function tearDown()
    {
        unset($this->violationBuilder, $this->constraint);
    }
}
