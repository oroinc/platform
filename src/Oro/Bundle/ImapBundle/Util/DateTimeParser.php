<?php

namespace Oro\Bundle\ImapBundle\Util;

class DateTimeParser
{
    /**
     * Convert a string to DateTime
     *
     * @param string $value
     *
     * @return \DateTime
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function parse($value)
    {
        $originalVal = $value;

        // remove "quoted-printable" encoded spaces if any
        $pos = strpos($value, '=20');
        if (false !== $pos) {
            $value = str_replace('=20', ' ', $value);
            if (strrpos($value, '?=') === strlen($value) - 2) {
                $value = substr($value, 0, -2);
                if (strrpos($value, '+') === strlen($value) - 4 || strrpos($value, '+') === strlen($value) - 4) {
                    $value .= '0';
                }
            }
        }
        // remove time zone comments if any
        $pos = strrpos($value, ')');
        if (false !== $pos) {
            $start = strrpos($value, '(');
            if (false !== $start) {
                $value = rtrim(substr_replace($value, '', $start, $pos - $start + 1));
            }
        }
        // replace UT time zone with UTC
        $pos = strrpos($value, ' UT');
        if (false !== $pos && $pos === strlen($value) - 3) {
            $value = substr($value, 0, -3) . ' UTC';
        }
        // set UTC time zone if no other one is specified
        $pos = strrpos($value, ':');
        if (false !== $pos
            && (
                false === strpos($value, '+', $pos + 1)
                && false === strpos($value, '-', $pos + 1)
                && false === strpos($value, ' ', $pos + 1)
            )
        ) {
            $value .= ' UTC';
        }

        $date = self::parseDateTime($value);
        if (!$date) {
            $err = self::getDateTimeLastError($value);

            // replace leading whitespace with zero in minutes and seconds
            $value = preg_replace('#: (\d)#', ':0$1', $value);
            $date = self::parseDateTime($value, 'D, d m Y H:i:s O');

            if (!$date && false !== strpos($value, ',')) {
                // handle case when invalid short day name given
                $value = substr($value, strpos($value, ',') + 1);
                $alphabeticalCharsLeft = trim(preg_replace('#[\W0-9]+#', '', $value));
                if (strlen($alphabeticalCharsLeft) > 0) {
                    $date = self::parseDateTime(ltrim($value), 'd M Y H:i:s O');
                }
            }

            if (!$date) {
                if ($originalVal === $value) {
                    throw new \InvalidArgumentException($err);
                } else {
                    throw new \InvalidArgumentException(
                        sprintf('%s Original value: "%s".', $err, $originalVal)
                    );
                }
            }
        }

        return $date;
    }

    /**
     * @param string $value
     * @param string $format
     *
     * @return \DateTime UTC date/time or FALSE
     */
    protected static function parseDateTime($value, $format = null)
    {
        $date = $format
            ? date_create_from_format($format, $value, new \DateTimeZone('UTC'))
            : date_create($value, new \DateTimeZone('UTC'));
        if ($date) {
            $date->setTimezone(new \DateTimeZone('UTC'));
        }

        return $date;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected static function getDateTimeLastError($value)
    {
        $msg = null;
        $errors = \DateTime::getLastErrors();
        if (!empty($errors['errors'])) {
            foreach ($errors['errors'] as $pos => $err) {
                $msg = sprintf('at position %d: %s', $pos, $err);
                break;
            }
        }
        if (!$msg && !empty($errors['warnings'])) {
            foreach ($errors['warnings'] as $pos => $err) {
                $msg = sprintf('at position %d: (warning) %s', $pos, $err);
                break;
            }
        }

        return sprintf(
            'Failed to parse time string "%s" %s.',
            $value,
            $msg ?: ': Unknown error'
        );
    }
}
