<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;

class FilterDateRangeConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var FilterDateRangeConverter */
    protected $converter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dateCompiler;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dateHelper;

    public function setUp()
    {
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->converter = $this->getMockBuilder('Oro\Bundle\FilterBundle\Expression\Date\Compiler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $settings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();
        $settings->expects($this->any())
            ->method('getTimeZone')
            ->willReturn('UTC');
        $doctrine         = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $aclHelper        = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateHelper = new DateHelper($settings, $doctrine, $aclHelper);

        $this->converter = new FilterDateRangeConverter(
            $this->formatter,
            $this->converter,
            $this->translator,
            $this->dateHelper
        );
    }

    public function testGetConvertedValueDefaultValues()
    {
        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $start       = clone $currentDate;
        $start       = $start->sub(new \DateInterval('P1M'));

        $result = $this->converter->getConvertedValue([]);

        $this->assertEquals($currentDate->format('M'), $result['end']->format('M'));
        $this->assertEquals($start->format('M'), $result['start']->format('M'));
    }

    public function testGetConvertedValueBetween()
    {
        $start = new \DateTime('2014-01-01', new \DateTimeZone('UTC'));
        $end   = new \DateTime('2015-01-01', new \DateTimeZone('UTC'));

        $result = $this->converter->getConvertedValue(
            [],
            [
                'value' => [
                    'start' => $start,
                    'end'   => $end
                ],
                'type'  => AbstractDateFilterType::TYPE_BETWEEN
            ]
        );

        $this->assertSame($end, $result['end']);
        $this->assertEquals($start, $result['start']);
    }

    public function testGetConvertedValueMoreThan()
    {
        $value = new \DateTime('2014-01-01', new \DateTimeZone('UTC'));

        $result = $this->converter->getConvertedValue(
            [],
            [
                'value' => [
                    'start' => $value,
                    'end'   => null
                ],
                'type'  => AbstractDateFilterType::TYPE_MORE_THAN
            ]
        );

        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->assertEquals($currentDate->format('M'), $result['end']->format('M'));
        $this->assertEquals($value, $result['start']);
    }

    public function testGetConvertedValueLessThan()
    {
        $value = new \DateTime('2014-01-01', new \DateTimeZone('UTC'));

        $result = $this->converter->getConvertedValue(
            [],
            [
                'value' => [
                    'end'   => $value,
                    'start' => null
                ],
                'type'  => AbstractDateFilterType::TYPE_LESS_THAN
            ]
        );

        $this->assertEquals(FilterDateRangeConverter::MIN_DATE, $result['start']->format('Y-m-d'));
        $this->assertEquals($value, $result['end']);
    }

    public function testGetViewValue()
    {
        $this->formatter->expects($this->exactly(2))
            ->method('formatDate')
            ->willReturnCallback(
                function ($input) {
                    return $input->format('Y-m-d');
                }
            );
        $start = new \DateTime('2014-01-01', new \DateTimeZone('UTC'));
        $end   = new \DateTime('2015-01-01', new \DateTimeZone('UTC'));

        $this->assertEquals(
            '2014-01-01 - 2015-01-01',
            $this->converter->getViewValue(
                [
                    'start' => $start,
                    'end'   => $end,
                    'type'  => AbstractDateFilterType::TYPE_BETWEEN,
                    'part'  => null
                ]
            )
        );
    }
}
