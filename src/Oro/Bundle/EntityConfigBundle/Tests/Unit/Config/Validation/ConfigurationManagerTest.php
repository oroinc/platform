<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Validation\ConfigurationManager;
use Oro\Bundle\EntityConfigBundle\Config\Validation\ConfigurationValidator;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Validation\Mock\SimpleEntityConfiguration;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Validation\Mock\SimpleFieldConfiguration;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigurationManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testEntityConfigCorrectDataInitialization()
    {
        $manager = new ConfigurationManager([new SimpleEntityConfiguration()]);

        $className = $manager->getClass(ConfigurationValidator::CONFIG_ENTITY_TYPE, 'simple');
        $configuration = $manager->getConfiguration(ConfigurationValidator::CONFIG_ENTITY_TYPE, 'simple');

        $this->assertStringContainsString(
            'SimpleEntityConfiguration',
            $className
        );

        $this->assertInstanceOf(
            ConfigurationInterface::class,
            $configuration
        );
    }

    public function testFieldConfigCorrectDataInitialization()
    {
        $manager = new ConfigurationManager([new SimpleFieldConfiguration()]);

        $className = $manager->getClass(ConfigurationValidator::CONFIG_FIELD_TYPE, 'simple');
        $configuration = $manager->getConfiguration(ConfigurationValidator::CONFIG_FIELD_TYPE, 'simple');

        $this->assertStringContainsString(
            'SimpleFieldConfiguration',
            $className
        );

        $this->assertInstanceOf(
            ConfigurationInterface::class,
            $configuration
        );
    }

    public function testWrongServiceInitialization()
    {
        $this->expectException(\TypeError::class);
        new ConfigurationManager([
            /**
             * Anonymous class emulate wrong Configuration class
             */
            new class() {
                public function fakeMethod()
                {
                    throw new \Exception('Method cannot be run.');
                }
            }
        ]);
    }

    public function testEntityConfigWrongSectorName()
    {
        $manager = new ConfigurationManager([new SimpleEntityConfiguration()]);

        $className = $manager->getClass(ConfigurationValidator::CONFIG_ENTITY_TYPE, 'wrong_name');
        $configuration = $manager->getConfiguration(ConfigurationValidator::CONFIG_ENTITY_TYPE, 'wrong_name');

        $this->assertEquals(
            null,
            $className
        );

        $this->assertEquals(
            null,
            $configuration
        );
    }


    public function testEntityConfigWrongType()
    {
        $manager = new ConfigurationManager([new SimpleEntityConfiguration()]);

        $className = $manager->getClass(ConfigurationValidator::CONFIG_FIELD_TYPE, 'simple');
        $configuration = $manager->getConfiguration(ConfigurationValidator::CONFIG_FIELD_TYPE, 'simple');

        $this->assertEquals(
            null,
            $className
        );

        $this->assertEquals(
            null,
            $configuration
        );
    }
}
