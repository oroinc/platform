<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Formatter\NumberTypeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class NumberTypeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var NumberTypeFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $numberFormatter = $this->createMock(NumberFormatter::class);

        $this->formatter = new NumberTypeFormatter($numberFormatter);
    }

    /**
     * @dataProvider formatTypeProvider
     */
    public function testFormatType(int $value, string $type, \Exception $exception = null)
    {
        if (null !== $exception) {
            $this->expectException(get_class($exception));
            $this->expectExceptionMessage($exception->getMessage());
        }
        $this->formatter->formatType($value, $type);
    }

    public function formatTypeProvider(): array
    {
        $value = 1;

        return [
            'type currency'           => [$value, NumberTypeFormatter::TYPE_CURRENCY],
            'type decimal'            => [$value, NumberTypeFormatter::TYPE_DECIMAL],
            'type integer'            => [$value, NumberTypeFormatter::TYPE_INTEGER],
            'type percent'            => [$value, NumberTypeFormatter::TYPE_PERCENT],
            'type not supported type' => [
                $value,
                'test',
                new InvalidArgumentException('Couldn\'t format "test" type')
            ],
        ];
    }
}
