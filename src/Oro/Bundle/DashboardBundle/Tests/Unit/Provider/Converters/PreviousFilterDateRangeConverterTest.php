<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\PreviousFilterDateRangeConverter;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PreviousFilterDateRangeConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var Compiler|\PHPUnit\Framework\MockObject\MockObject */
    private $dateCompiler;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var PreviousFilterDateRangeConverter */
    private $converter;

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

    public function testGetConvertedValueBetween()
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
                        'end'   => $end
                    ],
                    'type'  => AbstractDateFilterType::TYPE_BETWEEN
                ]
            ]
        );

        $this->assertEquals('2013-01-01', $result['start']->format('Y-m-d'));
        $this->assertEquals('2014-01-01', $result['end']->format('Y-m-d'));
    }
}
