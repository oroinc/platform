<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

/**
 * A set of method to simplify building "date" related columns in datagrids.
 */
class DateHelper
{
    /** @var LocaleSettings */
    private $localeSettings;

    /** @var string */
    private $offset;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * Wraps the given DQL expression with the convert timezone function
     * to convert the expression value to the current timezone.
     *
     * @param string $expression
     *
     * @return string
     */
    public function getConvertTimezoneExpression($expression)
    {
        $timeZoneOffset = $this->getTimeZoneOffset();
        if ('+00:00' !== $timeZoneOffset) {
            $expression = sprintf("CONVERT_TZ(%s, '+00:00', '%s')", $expression, $timeZoneOffset);
        }

        return $expression;
    }

    /**
     * Gets the current timezone offset with colon between hours and minutes,
     * e.g. "+02:00", "-01:00", etc.
     *
     * @return string
     */
    public function getTimeZoneOffset()
    {
        if (null === $this->offset) {
            $time = new \DateTime('now', new \DateTimeZone($this->localeSettings->getTimeZone()));
            $this->offset = $time->format('P');
        }

        return $this->offset;
    }
}
