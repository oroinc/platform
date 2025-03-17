<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\PreviousFilterDateRangeConverter;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PreviousFilterDateRangeConverterTest extends TestCase
{
    private DateTimeFormatterInterface&MockObject $formatter;
    private Compiler&MockObject $dateCompiler;
    private TranslatorInterface&MockObject $translator;
    private PreviousFilterDateRangeConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->formatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->dateCompiler = $this->createMock(Compiler::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->converter = new PreviousFilterDateRangeConverter(
            $this->formatter,
            $this->dateCompiler,
            $this->translator
        );
    }

    public function testGetConvertedValueBetween(): void
    {
        $start = new \DateTime('2014-01-01', new \DateTimeZone('UTC'));
        $end = new \DateTime('2014-12-31', new \DateTimeZone('UTC'));

        $result = $this->converter->getConvertedValue(
            [],
            true,
            [
                'converter_attributes' => [
                    'dateRangeField' => 'dateRange'
                ]
            ],
            [
                'dateRange' => [
                    'value' => [
                        'start' => $start,
                        'end' => $end
                    ],
                    'type' => AbstractDateFilterType::TYPE_BETWEEN
                ]
            ]
        );

        self::assertEquals('2013-01-01', $result['start']->format('Y-m-d'));
        self::assertEquals('2014-01-01', $result['end']->format('Y-m-d'));
    }
}
