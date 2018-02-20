<?php

namespace Oro\Bundle\LocaleBundle\Converter;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractDateTimeFormatConverter implements DateTimeFormatConverterInterface
{
    /**
     * @var DateTimeFormatter
     */
    protected $formatter;

    /**
     * ICU format => Default data
     *
     * http://userguide.icu-project.org/formatparse/datetime
     *
     * @var array
     */
    protected $defaultFormatMatch = array(
        'GGGGG'  => '',
        'GGGG'   => '',
        'GGG'    => '',
        'GG'     => '',
        'G'      => '',
        'yyyy'   => '',
        'yy'     => '',
        'y'      => '',
        'Y'      => '',
        'u'      => '',
        'U'      => '',
        'QQQQ'   => '',
        'QQQ'    => '',
        'QQ'     => '',
        'Q'      => '',
        'qqqq'   => '',
        'qqq'    => '',
        'qq'     => '',
        'q'      => '',
        'MMMMM'  => '',
        'MMMM'   => '',
        'MMM'    => '',
        'MM'     => '',
        'M'      => '',
        'LLLLL'  => '',
        'LLLL'   => '',
        'LLL'    => '',
        'LL'     => '',
        'L'      => '',
        'ww'     => '',
        'w'      => '',
        'W'      => '',
        'dd'     => '',
        'd'      => '',
        'D'      => '',
        'F'      => '',
        'g'      => '',
        'EEEEEE' => '',
        'EEEEE'  => '',
        'EEEE'   => '',
        'EEE'    => '',
        'EE'     => '',
        'E'      => '',
        'eeeeee' => '',
        'eeeee'  => '',
        'eeee'   => '',
        'eee'    => '',
        'ee'     => '',
        'e'      => '',
        'cccccc' => '',
        'ccccc'  => '',
        'cccc'   => '',
        'ccc'    => '',
        'cc'     => '',
        'c'      => '',
        'a'      => '',
        'hh'     => '',
        'h'      => '',
        'HH'     => '',
        'H'      => '',
        'kk'     => '',
        'k'      => '',
        'KK'     => '',
        'K'      => '',
        'mm'     => '',
        'm'      => '',
        'ss'     => '',
        's'      => '',
        'SSSS'   => '',
        'SSS'    => '',
        'SS'     => '',
        'S'      => '',
        'A'      => '',
        'zzzz'   => '',
        'zzz'    => '',
        'zz'     => '',
        'z'      => '',
        'ZZZZZ'  => '',
        'ZZZZ'   => '',
        'ZZZ'    => '',
        'ZZ'     => '',
        'Z'      => '',
        'OOOO'   => '',
        'O'      => '',
        'vvvv'   => '',
        'v'      => '',
        'VVVV'   => '',
        'VVV'    => '',
        'VV'     => '',
        'V'      => '',
        'XXXXX'  => '',
        'XXXX'   => '',
        'XXX'    => '',
        'XX'     => '',
        'X'      => '',
        'xxxxx'  => '',
        'xxxx'   => '',
        'xxx'    => '',
        'xx'     => '',
        'x'      => '',
    );

    /**
     * Property should be overridden in descendant classes
     *
     * @var array
     */
    protected $formatMatch = array();

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param DateTimeFormatter   $formatter
     * @param TranslatorInterface $translator
     */
    public function __construct(DateTimeFormatter $formatter, TranslatorInterface $translator)
    {
        $this->formatter  = $formatter;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getDayFormat($locale = null)
    {
        return $this->convertFormat(
            $this->convertToDayFormat(
                $this->formatter->getPattern(\IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE, $locale)
            )
        );
    }

    /**
     * Convert from 'd MMM y' to 'd MMM' format with different types of dates
     *
     * @param string $format
     * @return string
     */
    public function convertToDayFormat($format)
    {
        $regexp = [
            '/(\xe2\x80\x8f\x2fy)/',
            '/((,|\/|\.|\-|\s*)\s*[y]+$)|(^[y]+\s*(,|\/|\.|\-|\s*))/i',
            '/.[y]+ \'г\'\./i',
            '/(\.[y]+\.)|([y]+\.$)/i',
            '/སྤྱི་ལོ་[y]+ /i',
            '/\s*\'gada\'\s*/i',
        ];
        return trim(preg_replace($regexp, '', $format));
    }

    /**
     * {@inheritDoc}
     */
    public function getDateFormat($dateFormat = null, $locale = null)
    {
        return $this->getFormat($dateFormat, \IntlDateFormatter::NONE, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeFormat($timeFormat = null, $locale = null)
    {
        return $this->getFormat(\IntlDateFormatter::NONE, $timeFormat, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeFormat($dateFormat = null, $timeFormat = null, $locale = null)
    {
        return $this->getFormat($dateFormat, $timeFormat, $locale);
    }

    /**
     * @param int|string|null $dateType Constant of IntlDateFormatter (NONE, FULL, LONG, MEDIUM, SHORT) or it's name
     * @param int|string|null $timeType Constant of IntlDateFormatter (NONE, FULL, LONG, MEDIUM, SHORT) or it's name
     * @param string|null $locale
     * @return string
     */
    protected function getFormat($dateType, $timeType, $locale)
    {
        return $this->convertFormat($this->formatter->getPattern($dateType, $timeType, $locale));
    }

    /**
     * @param string $format
     * @return string
     */
    protected function convertFormat($format)
    {
        $formatMatch = array_merge($this->defaultFormatMatch, $this->formatMatch);

        return strtr($format, $formatMatch);
    }
}
