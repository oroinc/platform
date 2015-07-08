<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Formatter;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\UIBundle\Formatter\FormatterManager;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\Formatter\TestDefaultFormatter;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\Formatter\TestFormatter;

class FormatterManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormatterManager */
    protected $manager;

    protected function setUp()
    {
        $this->manager = new FormatterManager;
        $testDefaultFormatter = new TestDefaultFormatter();
        $testFormatter = new TestFormatter();
        $this->manager->addFormatter($testDefaultFormatter->getFormatterName(), $testDefaultFormatter);
        $this->manager->addFormatter($testFormatter->getFormatterName(), $testFormatter);
    }

    public function testFormat()
    {
        $arguments = ['argument1', 'argument2'];

        $this->assertEquals(
            'parameter:test_parameter,arguments:argument1,argument2',
            $this->manager->format('test_parameter', 'test_format_name', $arguments)
        );
        $this->assertEquals('test_value', $this->manager->format(null, 'test_format_name'));
        $this->setExpectedException(
            'Oro\Bundle\UIBundle\Exception\InvalidFormatterException',
            'Formatter not_existing_formatter not found'
        );
        $this->manager->format('test_parameter', 'not_existing_formatter');
    }

    /**
     * @dataProvider guessFormattersDataProvider
     * @param FieldConfigId $fieldConfigId
     * @param               $expected
     */
    public function testGuessFormatters(FieldConfigId $fieldConfigId, $expected)
    {
        $this->assertEquals($expected, $this->manager->guessFormatters($fieldConfigId));
    }

    public static function guessFormattersDataProvider()
    {
        return [
            'test formatters' => [
                new FieldConfigId('test', 'TestClass', 'testField', 'string'),
                [
                    'formatters' => ['test_default_format_name', 'test_format_name'],
                    'default_formatter' => 'test_default_format_name'
                ]
            ],
            'formatter not quessed[not supported type]' => [
                new FieldConfigId('test', 'TestClass', 'testField', 'datetime'),
                null
            ]
        ];
    }
}
