<?php

namespace Oro\Component\Layout\Tests\Unit\Block\OptionResolver;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

class OptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OptionsResolver
     */
    protected $optionResolver;

    public function setUp()
    {
        $this->optionResolver = new OptionsResolver();
    }

    public function testAccessorsAndResolve()
    {
        $this->optionResolver->setDefault('default_option', 'default_value');
        $this->optionResolver->setDefaults(
            [
                'default_option2' => 'default_value2',
                'required_option_with_default' => false,
            ]
        );

        // Defaults
        $this->assertTrue($this->optionResolver->hasDefault('default_option'));
        $this->assertTrue($this->optionResolver->hasDefault('required_option_with_default'));
        $this->assertFalse($this->optionResolver->hasDefault('not_existing_option'));

        // Required
        $this->optionResolver->setRequired('required_option');
        $this->optionResolver->setRequired('required_option_with_default');
        $this->assertTrue($this->optionResolver->isRequired('required_option'));
        $this->assertFalse($this->optionResolver->isRequired('default_option'));

        $this->assertEquals(
            ['required_option', 'required_option_with_default'],
            $this->optionResolver->getRequiredOptions()
        );

        // Missing
        $this->assertFalse($this->optionResolver->isMissing('required_option_with_default'));
        $this->assertTrue($this->optionResolver->isMissing('required_option'));
        $this->assertEquals(['required_option'], $this->optionResolver->getMissingOptions());

        // Defined
        $this->optionResolver->setDefined('defined_option');
        $this->assertTrue($this->optionResolver->isDefined('defined_option'));

        // Remove
        $this->optionResolver->remove('default_option2');
        $this->assertFalse($this->optionResolver->isDefined('default_option2'));

        // Resolve
        $expected = [
            'default_option' => 'default_value',
            'required_option_with_default' => false,
            'required_option' => false,
        ];
        $actual = $this->optionResolver->resolve(['required_option' => false]);
        $this->assertEquals($expected, $actual);
    }

    public function testArrayAccessAndCountable()
    {
        $this->optionResolver->setDefault('default_option', 'default_value');

        $this->optionResolver->setDefault(
            'lazy_option',
            function (Options $options) {
                // ArrayAccess
                $this->assertTrue(isset($options['default_option']));
                $this->assertFalse(isset($options['not_existing_option']));
                $this->assertEquals('default_value', $options['default_option']);

                // Countable
                $this->assertEquals(2, $options->count());
            }
        );
        $this->optionResolver->resolve([]);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\AccessException
     * @expectedExceptionMessage Setting options via array access is not supported. Use setDefault() instead.
     */
    public function testOffsetSet()
    {
        $this->optionResolver->setDefault('default_option', 'default_value');

        $this->optionResolver->setDefault(
            'lazy_option',
            function (Options $options) {
                $options['default_option'] = 'new_value';
            }
        );
        $this->optionResolver->resolve([]);
    }

    public function testClear()
    {
        $this->optionResolver->setRequired('test');
        $this->optionResolver->clear();
        $this->assertEquals([], $this->optionResolver->resolve([]));
    }
}
