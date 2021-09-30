<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Validation\ConfigurationManager;
use Oro\Bundle\EntityConfigBundle\Config\Validation\ConfigurationValidator;
use Oro\Bundle\EntityConfigBundle\Exception\EntityConfigValidationException;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Validation\Mock\SecondSimpleEntityConfiguration;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Validation\Mock\SimpleEntityConfiguration;

class ConfigurationValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testCorrectValidation()
    {
        $manager = new ConfigurationManager([new SimpleEntityConfiguration()]);
        $service = new ConfigurationValidator($manager);
        $service->validate(ConfigurationValidator::CONFIG_ENTITY_TYPE, 'simple', [
            'simple_string' => 'string',
            'simple_bool' => true,
            'simple_array' => ['array of string'],
        ]);

        $this->assertTrue(true);
    }

    public function testWrongConfigAttribute()
    {
        $manager = new ConfigurationManager([new SimpleEntityConfiguration()]);
        $service = new ConfigurationValidator($manager);
        $configAttributeNames = $manager->getConfigAttributeNamesByType(ConfigurationValidator::CONFIG_ENTITY_TYPE);
        $this->expectExceptionMessage('The "wrong_attribute" is not available entity config attribute. ' .
            'List of available: ' . implode(', ', $configAttributeNames));

        $service->validate(ConfigurationValidator::CONFIG_ENTITY_TYPE, 'wrong_attribute', []);
    }

    public function testWrongAttributeType()
    {
        $this->expectException(EntityConfigValidationException::class);
        $manager = new ConfigurationManager([new SimpleEntityConfiguration()]);
        $service = new ConfigurationValidator($manager);
        $service->validate(ConfigurationValidator::CONFIG_ENTITY_TYPE, 'simple', [
            'simple_bool' => 'string',
            'simple_array' => false,
        ]);
    }


    public function testAttributeMerging()
    {
        $manager = new ConfigurationManager([new SimpleEntityConfiguration(), new SecondSimpleEntityConfiguration()]);
        $service = new ConfigurationValidator($manager);
        $service->validate(ConfigurationValidator::CONFIG_ENTITY_TYPE, 'simple', [
            'simple_string' => 'string',
            'other_simple_string' => 'string',
        ]);

        $this->assertTrue(true);
    }
}
