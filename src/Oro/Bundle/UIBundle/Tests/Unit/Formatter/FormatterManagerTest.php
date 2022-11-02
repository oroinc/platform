<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Formatter;

use Oro\Bundle\UIBundle\Exception\InvalidFormatterException;
use Oro\Bundle\UIBundle\Formatter\FormatterManager;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\Formatter\TestDefaultFormatter;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\Formatter\TestFormatter;
use Symfony\Component\DependencyInjection\ServiceLocator;

class FormatterManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormatterManager */
    private $manager;

    protected function setUp(): void
    {
        $formatters = new ServiceLocator([
            'test_default_format_name' => function () {
                return new TestDefaultFormatter();
            },
            'test_format_name'         => function () {
                return new TestFormatter();
            }
        ]);

        $this->manager = new FormatterManager(
            $formatters,
            ['string' => 'test_default_format_name']
        );
    }

    public function testFormat()
    {
        $arguments = ['argument1', 'argument2'];

        self::assertEquals('test_default_value', $this->manager->format(null, 'test_default_format_name'));
        self::assertEquals(
            'value:test_value,arguments:argument1,argument2',
            $this->manager->format('test_value', 'test_format_name', $arguments)
        );
        self::assertEquals('test_value', $this->manager->format(null, 'test_format_name'));
    }

    public function testFormatByNotExistingFormatter()
    {
        $this->expectException(InvalidFormatterException::class);
        $this->expectExceptionMessage('The formatter "not_existing_formatter" does not exist.');

        $this->manager->format('test_value', 'not_existing_formatter');
    }

    /**
     * @dataProvider guessFormatterDataProvider
     */
    public function testGuessFormatter(string $type, ?string $expected)
    {
        self::assertSame(
            $expected,
            $this->manager->guessFormatter($type)
        );
    }

    public static function guessFormatterDataProvider(): array
    {
        return [
            ['string', 'test_default_format_name'],
            ['datetime', null]
        ];
    }
}
