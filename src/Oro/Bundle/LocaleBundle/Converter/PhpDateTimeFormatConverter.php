<?php

namespace Oro\Bundle\LocaleBundle\Converter;

class PhpDateTimeFormatConverter extends AbstractDateTimeFormatConverter
{
    const NAME = 'php';

    /**
     * ICU format => PHP date format
     *
     * http://userguide.icu-project.org/formatparse/datetime
     * http://php.net/manual/en/function.date.php
     *
     * @var array
     */
    protected $formatMatch = array(
        'yyyy'  => 'Y', // A full numeric representation of a year, 4 digits (1999 or 2003)
        'yy'    => 'y', // A two digit representation of a year (99 or 03)
        'y'     => 'Y', // A full numeric representation of a year, 4 digits (1999 or 2003)
        'Y'     => 'Y', // year of "Week of Year"
        'MMMM'  => 'F', // A full textual representation of a month, such as January or March
        'MMM'   => 'M', // A short textual representation of a month, three letters (Jan through Dec)
        'MM'    => 'm', // Numeric representation of a month, with leading zeros (01 through 12)
        'M'     => 'n', // Numeric representation of a month, without leading zeros (1 through 12)
        'LLLL'  => 'F', // A full textual representation of a month, such as January or March
        'LLL'   => 'M', // A short textual representation of a month, three letters (Jan through Dec)
        'LL'    => 'm', // Numeric representation of a month, with leading zeros (01 through 12)
        'L'     => 'n', // Numeric representation of a month, without leading zeros (1 through 12)
        'dd'    => 'd', // Day of the month, 2 digits with leading zeros (01 to 31)
        'd'     => 'j', // Day of the month without leading zeros (1 to 31)
        'D'     => 'z', // The day of the year, starting from 0 (0 through 365)
        'a'     => 'A', // Uppercase Ante meridiem and Post meridiem (AM or PM)
        'hh'    => 'h', // 12-hour format of an hour with leading zeros (01 through 12)
        'h'     => 'g', // 12-hour format of an hour without leading zeros (1 through 12)
        'HH'    => 'H', // 24-hour format of an hour with leading zeros (00 through 23)
        'H'     => 'G', // 24-hour format of an hour without leading zeros (0 through 23)
        'mm'    => 'i', // Minutes with leading zeros (00 to 59)
        'ss'    => 's', // Seconds, with leading zeros (00 to 59)
        'zzz'   => 'T', // Timezone abbreviation (EST, MDT)
        'zz'    => 'T', // Timezone abbreviation (EST, MDT)
        'z'     => 'T', // Timezone abbreviation (EST, MDT)
        'ZZZ'   => 'P', // Difference to Greenwich time (GMT) with colon between hours and minutes (+02:00)
        'ZZ'    => 'P', // Difference to Greenwich time (GMT) with colon between hours and minutes (+02:00)
        'Z'     => 'P', // Difference to Greenwich time (GMT) with colon between hours and minutes (+02:00)
        'VVVV'  => 'e', // Timezone identifier (UTC, GMT, Atlantic/Azores)
        'VVV'   => 'e', // Timezone identifier (UTC, GMT, Atlantic/Azores)
        'VV'    => 'e', // Timezone identifier (UTC, GMT, Atlantic/Azores)
        'V'     => 'e', // Timezone identifier (UTC, GMT, Atlantic/Azores)
    );

    /**
     * {@inheritDoc}
     */
    protected function convertFormat($format)
    {
        return str_replace(array('"', '\''), '', parent::convertFormat($format));
    }
}
