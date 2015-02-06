<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Converter\PhpDateTimeFormatConverter;

class PhpDateTimeFormatConverterTest extends AbstractFormatConverterTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createFormatConverter()
    {
        return new PhpDateTimeFormatConverter($this->formatter);
    }

    /**
     * {@inheritDoc}
     */
    public function getDateFormatDataProvider()
    {
        return array(
            'en default' => array('M j, Y', null, self::LOCALE_EN),
            'en custom'  => array('F j, Y', \IntlDateFormatter::LONG, self::LOCALE_EN),
            'ru default' => array('d.m.Y', null, self::LOCALE_RU),
            'ru custom'  => array('j F Y г.', \IntlDateFormatter::LONG, self::LOCALE_RU),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeFormatDataProvider()
    {
        return array(
            'en default' => array('g:i A', null, self::LOCALE_EN),
            'en custom'  => array('g:i:s A', \IntlDateFormatter::MEDIUM, self::LOCALE_EN),
            'ru default' => array('G:i', null, self::LOCALE_RU),
            'ru custom'  => array('G:i:s', \IntlDateFormatter::MEDIUM, self::LOCALE_RU),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeFormatDataProvider()
    {
        return array(
            'en default' => array('M j, Y g:i A', null, null, self::LOCALE_EN),
            'en custom'  => array(
                'F j, Y g:i:s A',
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_EN
            ),
            'ru default' => array('d.m.Y G:i', null, null, self::LOCALE_RU),
            'ru custom'  => array(
                'j F Y г. G:i:s',
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_RU
            ),
        );
    }
}
