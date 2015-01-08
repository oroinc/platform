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
     */
    public static function parse($value)
    {
        $originalVal = $value;

        // remove time zone comments if any
        $pos = strrpos($value, ')');
        if (false !== $pos) {
            if (false !== $start = strrpos($value, '(')) {
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
            $err  = self::getDateTimeLastError($value);
            $date = self::parseDateTime($value, 'D, d m Y H:i:s O');
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
        if (!$msg && !empty($errors['warnings'])) {
            foreach ($errors['errors'] as $pos => $err) {
                $msg = sprintf('at position %d: (warning) %s', $pos, $err);
                break;
            }
        }
        if (!empty($errors['errors'])) {
            foreach ($errors['errors'] as $pos => $err) {
                $msg = sprintf('at position %d: %s', $pos, $err);
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
