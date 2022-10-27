<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Formatter\DateTimeTypeFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateTimeTypeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeTypeFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $localeSettings = $this->createMock(LocaleSettings::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new DateTimeTypeFormatter($localeSettings, $translator);
    }

    /**
     * @dataProvider formatTypeProvider
     */
    public function testFormatType(string $value, string $type, \Exception $exception = null)
    {
        if (null !== $exception) {
            $this->expectException(get_class($exception));
            $this->expectExceptionMessage($exception->getMessage());
        }
        $this->formatter->formatType($value, $type);
    }

    public function formatTypeProvider(): array
    {
        $value = (new \DateTime())->format('d/m/Y H:i:s');

        return [
            'type datetime'           => [$value, DateTimeTypeFormatter::TYPE_DATETIME],
            'type date'               => [$value, DateTimeTypeFormatter::TYPE_DATETIME],
            'type time'               => [$value, DateTimeTypeFormatter::TYPE_DATETIME],
            'type not supported type' => [
                $value,
                'test',
                new InvalidArgumentException('Couldn\'t format "test" type')
            ],
        ];
    }
}
