<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\PreviousFilterDateRangeConverter;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;

class PreviousFilterDateRangeConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var PreviousFilterDateRangeConverter */
    protected $converter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dateCompiler;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dateHelper;

    public function setUp()
    {
        $this->converter = $this->getMockBuilder('Oro\Bundle\FilterBundle\Expression\Date\Compiler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
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
        $doctrine  = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateHelper    = new DateHelper($settings, $doctrine, $aclHelper);

        $this->converter = new PreviousFilterDateRangeConverter(
            $this->formatter,
            $this->converter,
            $this->translator,
            $this->dateHelper
        );
    }

    public function testGetConvertedValueBetween()
    {
        $start = new \DateTime('2014-01-01', new \DateTimeZone('UTC'));
        $end   = new \DateTime('2014-12-31', new \DateTimeZone('UTC'));

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
        $this->assertEquals('2013-12-31', $result['end']->format('Y-m-d'));
    }
}
