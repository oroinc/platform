<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationValidator;

class PermissionConfigurationValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PermissionConfigurationValidator
     */
    protected $validator;

    public function setUp()
    {
        $this->validator = new PermissionConfigurationValidator();
    }

    protected function tearDown()
    {
        unset($this->validator);
    }

    /**
     * @param array $config
     * @param InvalidConfigurationException|null $expectedException
     * @param string|null $expectedExceptionMessage
     *
     * @dataProvider validateProvider
     */
    public function testValidate(
        array $config,
        $expectedException = null,
        $expectedExceptionMessage = null
    ) {
        if ($expectedException) {
            $this->setExpectedException($expectedException, $expectedExceptionMessage);
        }
        $this->validator->validate($config);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            'bad name' => [
                'config' => ['PERMISSION.BAD.NAME' => []],
                'expectedException' => '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
                'expectedExceptionMessage' => 'The permission name "PERMISSION.BAD.NAME" contains illegal characters',
            ],
            'good name' => [
                'config' => ['PERMISSION_V-E-R-Y:GOOD' => []],
            ],
            'good and bad names' => [
                'config' => ['PERMISSION_V-E-R-Y:GOOD' => [], 'PERMISSION.BAD.NAME' => []],
                'expectedException' => '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
                'expectedExceptionMessage' => 'The permission name "PERMISSION.BAD.NAME" contains illegal characters',
            ],
        ];
    }
}
