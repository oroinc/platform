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
     * @dataProvider validateDataProvider
     *
     * @param array $configMap System config
     * @param string $value    Testing password
     * @param string $expected Expected violations count
     */
    public function testValidate(array $configMap, $value, $expected)
    {
        $validator = new PasswordComplexityValidator($this->getConfigManager($configMap));
        $context = $this->getMockBuilder(ExecutionContextInterface::class)->disableOriginalConstructor()->getMock();

        $context->expects($this->exactly($expected))
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        /** @var ExecutionContext $context */
        $validator->initialize($context);
        $validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            'no rules enabled' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'expected' => 0,
            ],
            'min length - invalid value' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 10],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'expected' => 1,

            ],
            'upper case  - invalid value' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'expected' => 1,

            ],
            'numbers  - invalid value' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'expected' => 1,

            ],
            'special chars  - invalid value' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, false],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'expected' => 1,

            ],
            '2 rules - invalid' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, false],
                ],
                'value' => 'password',
                'expected' => 2,

            ],
            '3 rules - invalid' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 0],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'password',
                'expected' => 3,

            ],
            'all rules - invalid' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 10],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'password',
                'expected' => 4,
            ],
            'all rules - valid' => [
                'configMap' => [
                    [PasswordComplexityValidator::CONFIG_MIN_LENGTH, false, false, null, 8],
                    [PasswordComplexityValidator::CONFIG_UPPER_CASE, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_NUMBERS, false, false, null, true],
                    [PasswordComplexityValidator::CONFIG_SPECIAL_CHARS, false, false, null, true],
                ],
                'value' => 'paSsw0rd!',
                'expected' => 0,
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
