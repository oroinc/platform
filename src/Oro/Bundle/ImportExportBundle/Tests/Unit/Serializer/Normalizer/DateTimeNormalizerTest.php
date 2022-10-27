<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Formatter\ExcelDateTimeTypeFormatter;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DateTimeNormalizer;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateTimeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeNormalizer */
    private $normalizer;

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $translator = $this->createMock(TranslatorInterface::class);

        $this->normalizer = new DateTimeNormalizer();
        $this->normalizer->setFormatter(new ExcelDateTimeTypeFormatter($this->localeSettings, $translator));
    }

    public function testSupportsDenormalization()
    {
        $this->assertFalse($this->normalizer->supportsDenormalization([], 'stdClass'));
        $this->assertFalse($this->normalizer->supportsDenormalization([], 'DateTime'));
        $this->assertTrue($this->normalizer->supportsDenormalization('2013-12-31', 'DateTime'));
    }

    /**
     * @dataProvider testSupportsNormalizationProvider
     */
    public function testSupportsNormalization(mixed $data, bool $result)
    {
        $this->assertEquals($result, $this->normalizer->supportsNormalization($data, null, []));
    }

    public function testSupportsNormalizationProvider(): array
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
                new \stdClass,
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
     * @dataProvider normalizeProvider
     */
    public function testNormalize(
        string $expected,
        \DateTime $date,
        ?string $locale,
        ?string $timezone,
        array $context
    ) {
        if (null !== $locale) {
            $this->localeSettings->expects($this->any())
                ->method('getLocale')
                ->willReturn($locale);
        }

        if (null !== $timezone) {
            $this->localeSettings->expects($this->any())
                ->method('getTimezone')
                ->willReturn($timezone);
        }

        $this->assertEquals(
            $expected,
            $this->normalizer->normalize($date, null, $context)
        );
    }

    public function testNormalizeWithoutFormatter()
    {
        $normalizer = new DateTimeNormalizer();

        $date = new \DateTime('2013-12-31 23:59:59+0200');
        $expected = '2013-12-31T23:59:59+0200';
        $context = ['type' => 'datetime'];

        $this->assertEquals(
            $expected,
            $normalizer->normalize($date, null, $context)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeProvider(): array
    {
        $date = new \DateTime('2013-12-31 23:59:59+0200');

        return [
            // Tests normalization with default formats
            'Normalize data without type and format'                => [
                '2013-12-31T23:59:59+0200',
                $date,
                null,
                null,
                []
            ],
            'Normalize data with unknown type'                      => [
                '2013-12-31T23:59:59+0200',
                $date,
                null,
                null,
                ['type' => 'unknown']
            ],
            // Test normalization depends on context format
            'Normalize data depends on DateTime::ISO8601 format'    => [
                '2013-12-31T23:59:59+0200',
                $date,
                null,
                null,
                ['type' => 'datetime', 'format' => \DateTime::ISO8601]
            ],
            'Normalize data depends on "Y-m-d" format'              => [
                '2013-12-31',
                $date,
                null,
                null,
                ['type' => 'date', 'format' => 'Y-m-d']
            ],
            'Normalize data depends on " H:i:s" format'             => [
                '23:59:59',
                $date,
                null,
                null,
                ['type' => 'time', 'format' => 'H:i:s']
            ],
            // Test normalization depends on FR locale and context type
            'Normalize data depends on FR locale and datetime type' => [
                '31/12/2013 22:59:59', // DateTime should be shown in Europe/Paris timezone.
                $date,
                'fr',
                'Europe/Paris',
                ['type' => 'datetime']
            ],
            'Normalize data depends on FR locale and date type'     => [
                '31/12/2013', // Date should be shown in UTC timezone.
                $date,
                'fr',
                'Europe/Paris',
                ['type' => 'date']
            ],
            'Normalize data depends on FR locale and time type'     => [
                '21:59:59', // Time should be shown in UTC timezone.
                $date,
                'fr',
                'Europe/Paris',
                ['type' => 'time']
            ],
            // Test normalization depends on EN locale and context type
            'Normalize data depends on EN locale and datetime type' => [
                '12/31/2013 16:59:59', // DateTime should be shown in America/New_York timezone.
                $date,
                'en',
                'America/New_York',
                ['type' => 'datetime']
            ],
            'Normalize data depends on EN locale and date type'     => [
                '12/31/2013', // Date should be shown in UTC timezone.
                $date,
                'en',
                'America/New_York',
                ['type' => 'date']
            ],
            'Normalize data depends on EN locale and time type'     => [
                '21:59:59', // Time should be shown in UTC timezone.
                $date,
                'en',
                'America/New_York',
                ['type' => 'time']
            ],
            // Test normalization depends on DE locale and context type
            'Normalize data depends on DE locale and datetime type' => [
                '01.01.2014 06:59:59', // DateTime should be shown in Asia/Tokyo timezone.
                $date,
                'de',
                'Asia/Tokyo',
                ['type' => 'datetime']
            ],
            'Normalize data depends on DE locale and date type'     => [
                '31.12.2013', // Date should be shown in UTC timezone.
                $date,
                'de',
                'Asia/Tokyo',
                ['type' => 'date']
            ],
            'Normalize data depends on DE locale and time type'     => [
                '21:59:59', // Time should be shown in UTC timezone.
                $date,
                'de',
                'Asia/Tokyo',
                ['type' => 'time']
            ]
        ];
    }

    /**
     * @dataProvider denormalizeProvider
     *
     * @param \DateTime $expected
     * @param string    $data
     * @param string    $locale
     * @param string    $timezone
     * @param array     $context
     */
    public function testDenormalize(
        \DateTime $expected,
        string $data,
        ?string $locale,
        ?string $timezone,
        array $context
    ) {
        if (null !== $locale) {
            $this->localeSettings->expects($this->any())
                ->method('getLocale')
                ->willReturn($locale);
        }

        if (null !== $timezone) {
            $this->localeSettings->expects($this->any())
                ->method('getTimezone')
                ->willReturn($timezone);
        }

        $this->assertEquals(
            $expected,
            $this->normalizer->denormalize($data, 'DateTime', null, $context)
        );
    }

    public function testDenormalizeWithoutFormatter()
    {
        $data = '2013-12-31T23:59:59+0200';
        $expected = new \DateTime('2013-12-31 23:59:59+0200');
        $context = ['type' => 'datetime'];

        $this->assertEquals(
            $expected,
            $this->normalizer->denormalize($data, 'DateTime', null, $context)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function denormalizeProvider(): array
    {
        return [
            // Tests denormalization with default formats
            'Denormalize data without type and format'                => [
                new \DateTime('2013-12-31 23:59:59+0200'),
                '2013-12-31T23:59:59+0200',
                null,
                null,
                []
            ],
            // Test denormalization depends on context format
            'Denormalize data depends on DateTime::ISO8601 format'    => [
                new \DateTime('2013-12-31T23:59:59+0200'),
                '2013-12-31T23:59:59+0200',
                null,
                null,
                ['type' => 'datetime', 'format' => \DateTime::ISO8601]
            ],
            'Denormalize data depends on "Y-m-d" format'              => [
                new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('UTC')),
                '2013-12-31',
                null,
                null,
                ['type' => 'date', 'format' => 'Y-m-d']
            ],
            'Denormalize data depends on " H:i:s" format'             => [
                new \DateTime('1970-01-01 23:59:59', new \DateTimeZone('UTC')),
                '23:59:59',
                null,
                null,
                ['type' => 'time', 'format' => 'H:i:s']
            ],
            // Test denormalization depends on FR locale and context type
            'Denormalize data depends on FR locale and datetime type' => [
                new \DateTime('2013-12-31T23:59:59+0200'),
                '31/12/2013 22:59:59',
                'fr',
                'Europe/Paris',
                ['type' => 'datetime']
            ],
            'Denormalize data depends on FR locale and date type'     => [
                new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('UTC')),
                '31/12/2013',
                'fr',
                'Europe/Paris',
                ['type' => 'date']
            ],
            'Denormalize data depends on FR locale and time type'     => [
                new \DateTime('1970-01-01 23:59:59', new \DateTimeZone('UTC')),
                '23:59:59',
                'fr',
                'Europe/Paris',
                ['type' => 'time']
            ],
            // Test denormalization depends on EN locale and context type
            'Denormalize data depends on EN locale and datetime type' => [
                new \DateTime('2013-12-31T23:59:59+0200'),
                '12/31/2013 16:59:59',
                'en',
                'America/New_York',
                ['type' => 'datetime']
            ],
            'Denormalize data depends on EN locale and date type'     => [
                new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('UTC')),
                '12/31/2013',
                'en',
                'America/New_York',
                ['type' => 'date']
            ],
            'Denormalize data depends on EN locale and time type'     => [
                new \DateTime('1970-01-01 23:59:59', new \DateTimeZone('UTC')),
                '23:59:59',
                'en',
                'America/New_York',
                ['type' => 'time']
            ],
            // Test denormalization depends on DE locale and context type
            'Denormalize data depends on DE locale and datetime type' => [
                new \DateTime('2013-12-31T23:59:59+0200'),
                '01.01.2014 06:59:59',
                'de',
                'Asia/Tokyo',
                ['type' => 'datetime']
            ],
            'Denormalize data depends on DE locale and date type'     => [
                new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('UTC')),
                '31.12.2013',
                'de',
                'Asia/Tokyo',
                ['type' => 'date']
            ],
            'Denormalize data depends on DE locale and time type'     => [
                new \DateTime('1970-01-01 23:59:59', new \DateTimeZone('UTC')),
                '23:59:59',
                'de',
                'Asia/Tokyo',
                ['type' => 'time']
            ]
        ];
    }

    public function testDenormalizeException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid datetime "qwerty", expected format Y-m-d\TH:i:sO.');

        $this->normalizer->denormalize('qwerty', 'DateTime');
    }
}
