<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\ImportExport\Serializer\Normalizer;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ImportExportBundle\Formatter\ExcelDateTimeTypeFormatter;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DateTimeNormalizer;

class DateTimeNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  DateTimeNormalizer */
    protected $normalizer;

    /** @var  LocaleSettings|PHPUnit_Framework_MockObject_MockObject */
    protected $localeSettings;

    protected function setUp()
    {
        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $formatter        = new ExcelDateTimeTypeFormatter($this->localeSettings, $translator);
        $this->normalizer = new DateTimeNormalizer();
        $this->normalizer->setFormatter($formatter);
    }

    public function testSupportsDenormalization()
    {
        $this->assertFalse($this->normalizer->supportsDenormalization([], 'stdClass'));
        $this->assertFalse($this->normalizer->supportsDenormalization([], 'DateTime'));
        $this->assertTrue($this->normalizer->supportsDenormalization('2013-12-31', 'DateTime'));
    }

    /**
     * @dataProvider testSupportsNormalizationProvider
     *
     * @param mixed $data
     * @param bool  $result
     */
    public function testSupportsNormalization($data, $result)
    {
        $this->assertEquals($result, $this->normalizer->supportsNormalization($data, null, []));
    }

    /**
     * @return array
     */
    public function testSupportsNormalizationProvider()
    {
        $dateTime = new \DateTime();

        return [
            'supports datetime'   => [
                $dateTime,
                true
            ],
            'supports date'       => [
                $dateTime,
                true
            ],
            'supports time'       => [
                $dateTime,
                true
            ],
            'not supports object' => [
                new \StdClass,
                false
            ],
            'empty data'          => [
                [],
                false
            ],
            'not supports string' => [
                $dateTime->format('d/m/Y H:i:s'),
                false
            ]
        ];
    }

    /**
     * @dataProvider testNormalizeProvider
     *
     * @param string    $expected
     * @param \DateTime $date
     * @param string    $locale
     * @param array     $context
     */
    public function testNormalize($expected, $date, $locale, $context)
    {
        if ($locale !== null) {
            $this->localeSettings->expects($this->any())->method('getLocale')->willReturn($locale);
        }

        $this->assertEquals(
            $expected,
            $this->normalizer->normalize($date, null, $context)
        );
    }

    /**
     * @return array of [
     *      'Expected DateTime string',
     *      'DateTime object for normalization',
     *      'Locale',
     *      'Context'
     *   ]
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testNormalizeProvider()
    {
        $date = new \DateTime('2013-12-31 23:59:59+0200');

        return [
            // Tests normalization with default formats
            'Normalize data without type and format'                => [
                '2013-12-31T23:59:59+0200',
                $date,
                null,
                []
            ],
            'Normalize data with unknown type'                      => [
                '2013-12-31T23:59:59+0200',
                $date,
                null,
                ['type' => 'unknown']
            ],
            // Test normalization depends on context format
            'Normalize data depends on DateTime::ISO8601 format'    => [
                '2013-12-31T23:59:59+0200',
                $date,
                null,
                ['type' => 'datetime', 'format' => \DateTime::ISO8601]
            ],
            'Normalize data depends on "Y-m-d" format'              => [
                '2013-12-31',
                $date,
                null,
                ['type' => 'date', 'format' => 'Y-m-d']
            ],
            'Normalize data depends on " H:i:s" format'             => [
                '23:59:59',
                $date,
                null,
                ['type' => 'time', 'format' => 'H:i:s']
            ],
            // Test normalization depends on FR locale and context type
            'Normalize data depends on FR locale and datetime type' => [
                '31/12/2013 23:59:59',
                $date,
                'fr',
                ['type' => 'datetime']
            ],
            'Normalize data depends on FR locale and date type'     => [
                '31/12/2013',
                $date,
                'fr',
                ['type' => 'date']
            ],
            'Normalize data depends on FR locale and time type'     => [
                '23:59:59',
                $date,
                'fr',
                ['type' => 'time']
            ],
            // Test normalization depends on EN locale and context type
            'Normalize data depends on EN locale and datetime type' => [
                '12/31/2013 23:59:59',
                $date,
                'en',
                ['type' => 'datetime']
            ],
            'Normalize data depends on EN locale and date type'     => [
                '12/31/2013',
                $date,
                'en',
                ['type' => 'date']
            ],
            'Normalize data depends on EN locale and time type'     => [
                '23:59:59',
                $date,
                'en',
                ['type' => 'time']
            ],
            // Test normalization depends on DE locale and context type
            'Normalize data depends on DE locale and datetime type' => [
                '31.12.2013 23:59:59',
                $date,
                'de',
                ['type' => 'datetime']
            ],
            'Normalize data depends on DE locale and date type'     => [
                '31.12.2013',
                $date,
                'de',
                ['type' => 'date']
            ],
            'Normalize data depends on DE locale and time type'     => [
                '23:59:59',
                $date,
                'de',
                ['type' => 'time']
            ]
        ];
    }

    /**
     * @dataProvider testDenormalizeProvider
     *
     * @param \DateTime $expected
     * @param string    $data
     * @param string    $locale
     * @param array     $context
     */
    public function testDenormalize($expected, $data, $locale, $context)
    {
        if ($locale !== null) {
            $this->localeSettings->expects($this->any())->method('getLocale')->willReturn($locale);
        }

        $this->assertEquals(
            $expected,
            $this->normalizer->denormalize($data, 'DateTime', null, $context)
        );
    }

    /**
     * @return array of [
     *      'Expected DateTime object',
     *      'DateTime string for denormalization',
     *      'Locale',
     *      'Context'
     *   ]
     */
    public function testDenormalizeProvider()
    {
        return [
            // Tests denormalization with default formats
            'Denormalize data without type and format'                => [
                new \DateTime('2013-12-31 23:59:59+0200'),
                '2013-12-31T23:59:59+0200',
                null,
                []
            ],
            // Test denormalization depends on context format
            'Denormalize data depends on DateTime::ISO8601 format'    => [
                new \DateTime('2013-12-31T23:59:59+0200'),
                '2013-12-31T23:59:59+0200',
                null,
                ['type' => 'datetime', 'format' => \DateTime::ISO8601]
            ],
            'Denormalize data depends on "Y-m-d" format'              => [
                new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('UTC')),
                '2013-12-31',
                null,
                ['type' => 'date', 'format' => 'Y-m-d']
            ],
            'Denormalize data depends on " H:i:s" format'             => [
                new \DateTime('1970-01-01 23:59:59', new \DateTimeZone('UTC')),
                '23:59:59',
                null,
                ['type' => 'time', 'format' => 'H:i:s']
            ],
            // Test denormalization depends on FR locale and context type
            'Denormalize data depends on FR locale and datetime type' => [
                new \DateTime('2013-12-31T23:59:59'),
                '31/12/2013 23:59:59',
                'fr',
                ['type' => 'datetime']
            ],
            'Denormalize data depends on FR locale and date type'     => [
                new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('UTC')),
                '31/12/2013',
                'fr',
                ['type' => 'date']
            ],
            'Denormalize data depends on FR locale and time type'     => [
                new \DateTime('1970-01-01 23:59:59', new \DateTimeZone('UTC')),
                '23:59:59',
                'fr',
                ['type' => 'time']
            ],
            // Test denormalization depends on EN locale and context type
            'Denormalize data depends on EN locale and datetime type' => [
                new \DateTime('2013-12-31T23:59:59'),
                '12/31/2013 23:59:59',
                'en',
                ['type' => 'datetime']
            ],
            'Denormalize data depends on EN locale and date type'     => [
                new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('UTC')),
                '12/31/2013',
                'en',
                ['type' => 'date']
            ],
            'Denormalize data depends on EN locale and time type'     => [
                new \DateTime('1970-01-01 23:59:59', new \DateTimeZone('UTC')),
                '23:59:59',
                'en',
                ['type' => 'time']
            ],
            // Test denormalization depends on DE locale and context type
            'Denormalize data depends on DE locale and datetime type' => [
                new \DateTime('2013-12-31T23:59:59'),
                '31.12.2013 23:59:59',
                'de',
                ['type' => 'datetime']
            ],
            'Denormalize data depends on DE locale and date type'     => [
                new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('UTC')),
                '31.12.2013',
                'de',
                ['type' => 'date']
            ],
            'Denormalize data depends on DE locale and time type'     => [
                new \DateTime('1970-01-01 23:59:59', new \DateTimeZone('UTC')),
                '23:59:59',
                'de',
                ['type' => 'time']
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Serializer\Exception\RuntimeException
     * @expectedExceptionMessage Invalid datetime "qwerty", expected format Y-m-d\TH:i:sO.
     */
    public function testDenormalizeException()
    {
        $this->normalizer->denormalize('qwerty', 'DateTime', null);
    }
}
