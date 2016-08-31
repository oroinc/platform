<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms between time duration in seconds (scalar) and duration encoded string.
 * Straight transform converts a scalar (seconds) into a JIRA style encoded string (1h 2m 3s)
 * with zero values omitted (e.g. 3600 => 1h)
 *
 * Reverse transform converts an encoded time string to an integer (seconds). Result is rounded up.
 * Supported encodings:
 * - JIRA style encoding (0.0h 0.0m 0.0s).
 *   Supports fractions (with dot delimiter) to the first digit.
 *   Result is rounded to the second. Any part could be omitted.
 * - Column style encoding h:m:s (0:0:0)
 *   Hours and minutes can be omitted, so 0:0 is treated min:sec, 0 as sec.
 *   Parts are rounded up (to int). All PHP string-to-int conversion rules apply.
 *    Invalid numbers result to 0's (1a:b2:3.5m => 1:0:4). Missing leading zeros are also valid (1: => 0:1:0).
 * In both styles time parts are cumulative, so '1m 119.5s' (or '1:119.5') becomes 3 min.
 *
 * Supports "," and "." as decimal delimiter
 */
class DurationToStringTransformer implements DataTransformerInterface
{
    const DURATION_JIRA_REGEX = '/^
                                (?:(?:(\d+(?:[\.,]\d{0,2})?)?)h
                                (?:[\s]*|$))?(?:(?:(\d+(?:[\.,]\d{0,2})?)?)m
                                (?:[\s]*|$))?(?:(?:(\d+(?:[\.,]\d{0,2})?)?)s)?
                                $/ix';
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!is_scalar($value)) {
            throw new UnexpectedTypeException($value, 'scalar');
        }

        // create \DateInterval from integer
        try {
            $dateInterval = new \DateInterval('PT' . round($value) . 'S');
            // since \DateInterval does not handle carryovers, we need to use \DateTime::diff
            $dateTime = new \DateTimeImmutable();
            $interval = $dateTime->diff($dateTime->add($dateInterval));
        } catch (\Exception $e) {
            throw new TransformationFailedException('Duration too long to convert.');
        }

        return $this->dateIntervalToString($interval);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value || (is_string($value) && '' === trim($value))) {
            return null;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $parts = $this->getTimeParts($value);

        return round($parts['h'] * 3600 + $parts['m'] * 60 + $parts['s']);
    }

    /**
     * Parse duration string and returns a map of time parts
     *
     * @param string $time
     *
     * @return array
     */
    private function getTimeParts($time)
    {
        $time = trim((string)$time);

        // matches JIRA style string
        if (preg_match_all(self::DURATION_JIRA_REGEX, $time, $matches)) {
            return [
                'h' => $this->getFloat($matches[1][0]),
                'm' => $this->getFloat($matches[2][0]),
                's' => $this->getFloat($matches[3][0]),
            ];
        }

        // parse Column style (h:m:s)
        // make sure we have all parts when hours or minutes are omitted
        $parts = array_pad(explode(':', $time), -3, 0);

        return [
            'h' => $this->getFloat($parts[0]),
            'm' => $this->getFloat($parts[1]),
            's' => round($this->getFloat($parts[2])),
        ];
    }

    /**
     * Returns float from a string. Supports either ',' or '.' as decimal delimiter.
     *
     * @param string $string
     *
     * @return float
     */
    private function getFloat($string)
    {
        return (float) str_replace(',', '.', $string);
    }

    /**
     * Convert \DateInterval to JIRA style encoded time string (1h 2m 3s). Zero values are omitted.
     *
     * @param \DateInterval $dateInterval
     *
     * @return string
     */
    private function dateIntervalToString(\DateInterval $dateInterval)
    {
        $encoded = [];
        $hours = $dateInterval->days * 24 + $dateInterval->h;
        $minutes = $dateInterval->i;
        $seconds = $dateInterval->s;

        if ($hours) {
            $encoded[] = $hours . 'h';
        }
        if ($minutes) {
            $encoded[] = $minutes . 'm';
        }
        if ($seconds) {
            $encoded[] = $seconds . 's';
        }

        return empty($encoded) ? '0s' : join(' ', $encoded);
    }
}
