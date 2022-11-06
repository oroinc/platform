<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\DateRangeType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class DateRangeTypeTest extends AbstractTypeTestCase
{
    /** @var DateRangeType */
    private $type;

    /** @var string */
    protected $defaultTimezone = 'Pacific/Honolulu';

    protected function setUp(): void
    {
        $localeSettings = $this->getMockBuilder(LocaleSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTimezone'])
            ->getMock();

        $localeSettings->expects(self::any())
            ->method('getTimezone')
            ->willReturn($this->defaultTimezone);

        $this->type = new DateRangeType($localeSettings);
        $this->formExtensions[] = new PreloadedExtension([$this->type], []);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType(): AbstractType
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider(): array
    {
        return [
            [
                'defaultOptions' => [
                    'field_type' => DateType::class,
                    'field_options' => [],
                    'start_field_options' => [
                        'html5' => false,
                    ],
                    'end_field_options' => [
                        'html5' => false,
                    ],
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider(): array
    {
        return [
            'custom format' => [
                'bindData' => ['start' => 'Jan 12, 1970', 'end' => 'Jan 12, 2013'],
                'formData' => [
                    'start' => $this->createDateTime('1970-01-12', 'UTC'),
                    'end' => $this->createDateTime('2013-01-12', 'UTC'),
                ],
                'viewData' => [
                    'value' => ['start' => 'Jan 12, 1970', 'end' => 'Jan 12, 2013'],
                ],
                'customOptions' => [
                    'field_options' => [
                    'model_timezone' => 'UTC',
                    'view_timezone' => 'UTC',
                    'format' => \IntlDateFormatter::MEDIUM
                    ]
                ]
            ]
        ];
    }

    /**
     * Creates date time object from date string
     *
     * @throws \Exception
     */
    private function createDateTime(
        string $dateString,
        string $timeZone = null,
        ?string $format = 'yyyy-MM-dd'
    ): \DateTime {
        $pattern = $format ?: null;

        if (!$timeZone) {
            $timeZone = date_default_timezone_get();
        }

        $calendar = \IntlDateFormatter::GREGORIAN;
        $intlDateFormatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            $timeZone,
            $calendar,
            $pattern
        );
        $intlDateFormatter->setLenient(false);
        $timestamp = $intlDateFormatter->parse($dateString);

        if (intl_get_error_code() != 0) {
            throw new \Exception(intl_get_error_message());
        }

        // read timestamp into DateTime object - the formatter delivers in UTC
        $dateTime = new \DateTime(sprintf('@%s UTC', $timestamp));
        if ('UTC' !== $timeZone) {
            try {
                $dateTime->setTimezone(new \DateTimeZone($timeZone));
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $dateTime;
    }
}
