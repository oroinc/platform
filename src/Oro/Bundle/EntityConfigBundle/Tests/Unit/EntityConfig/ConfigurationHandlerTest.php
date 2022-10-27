<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;
use Oro\Bundle\EntityConfigBundle\Exception\EntityConfigValidationException;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock\SecondSimpleEntityConfiguration;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock\SimpleEntityConfiguration;
use PHPUnit\Framework\TestCase;

class ConfigurationHandlerTest extends TestCase
{
    public function testCorrectValidation()
    {
        $handler = new ConfigurationHandler(new \ArrayIterator([new SimpleEntityConfiguration()]));

        $expected = [
            'simple_string' => 'string',
            'simple_bool' => true,
            'simple_array' => ['array of string'],
        ];
        $result = $handler->process(ConfigurationHandler::CONFIG_ENTITY_TYPE, 'simple', $expected, 'foo');

        $this->assertEquals($result, $expected);
    }

    public function testWrongConfigAttribute()
    {
        $handler = new ConfigurationHandler(new \ArrayIterator([new SimpleEntityConfiguration()]));

        $this->expectException(EntityConfigValidationException::class);
        $this->expectExceptionMessage('Invalid entity config for "foo": Unrecognized scope "wrong_attribute". ' .
            'Available scopes are "simple".');
        $handler->process(
            ConfigurationHandler::CONFIG_ENTITY_TYPE,
            'wrong_attribute',
            ['wrong_attribute' => ['foo' => 'bar']],
            'foo'
        );
    }

    public function testWrongAttributeScope()
    {
        $handler = new ConfigurationHandler(new \ArrayIterator([new SimpleEntityConfiguration()]));

        $this->expectException(EntityConfigValidationException::class);
        $this->expectExceptionMessage('Invalid entity config for "foo": Invalid type for path "simple.simple_bool".' .
            ' Expected "bool", but got "string".');
        $handler->process(ConfigurationHandler::CONFIG_ENTITY_TYPE, 'simple', [
            'simple_bool' => 'string',
            'simple_array' => false,
        ], 'foo');
    }

    public function testAttributeMergingAndDefaultValuesSet()
    {
        $handler = new ConfigurationHandler(
            new \ArrayIterator([new SimpleEntityConfiguration(), new SecondSimpleEntityConfiguration()])
        );
        $result = $handler->process(ConfigurationHandler::CONFIG_ENTITY_TYPE, 'simple', [
            'simple_string' => 'string',
            'other_simple_string' => 'string',
        ], 'foo');

        $this->assertEquals([
            'simple_string' => 'string',
            'simple_bool' => false,
            'other_simple_string' => 'string'
        ], $result);
    }
}
