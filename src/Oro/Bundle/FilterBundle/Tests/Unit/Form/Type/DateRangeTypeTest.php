<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\DateRangeType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class DateRangeTypeTest extends AbstractTypeTestCase
{
    /** @var DateRangeType */
    protected $type;

    /** @var string */
    protected $defaultLocale = 'en';

    /**
     * @var string
     */
    protected $defaultTimezone = 'Pacific/Honolulu';

    protected function setUp()
    {
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(array('getTimezone'))
            ->getMock();

        $localeSettings->expects($this->any())
            ->method('getTimezone')
            ->will($this->returnValue($this->defaultTimezone));

        $this->type = new DateRangeType($localeSettings);
        $this->formExtensions[] = new PreloadedExtension([$this->type], []);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestFormType()
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionsDataProvider()
    {
        return array(
            array(
                'defaultOptions' => array(
                    'field_type' => DateType::class,
                    'field_options' => array(),
                    'start_field_options' => array(),
                    'end_field_options' => array(),
                )
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function bindDataProvider()
    {
        return array(
            'custom format' => array(
                'bindData' => array('start' => 'Jan 12, 1970', 'end' => 'Jan 12, 2013'),
                'formData' => array(
                    'start' => $this->createDateTime('1970-01-12', 'UTC'),
                    'end' => $this->createDateTime('2013-01-12', 'UTC'),
                ),
                'viewData' => array(
                    'value' => array('start' => 'Jan 12, 1970', 'end' => 'Jan 12, 2013'),
                ),
                'customOptions' => array(
                    'field_options' => array(
                    'model_timezone' => 'UTC',
                    'view_timezone' => 'UTC',
                    'format' => \IntlDateFormatter::MEDIUM
                    )
                )
            )
        );
    }

    /**
     * Creates date time object from date string
     *
     * @param string $dateString
     * @param string|null $timeZone
     * @param string $format
     * @return \DateTime
     * @throws \Exception
     */
    private function createDateTime(
        $dateString,
        $timeZone = null,
        $format = 'yyyy-MM-dd'
    ) {
        $pattern = $format ? $format : null;

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
