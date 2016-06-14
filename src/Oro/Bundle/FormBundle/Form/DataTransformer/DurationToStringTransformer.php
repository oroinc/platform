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
 *   Parts are converted to int, thus trailing non-digits are trimmed, while leading will
 *   result to 0's (1a:b2:3.5m => 1:0:3). Missing leading zeros are also valid (:1: => 0:1:0).
 * In both styles time parts are cumulative, so '1m 120s' (or '1:120') becomes 3 min.
 */
class DurationToStringTransformer implements DataTransformerInterface
{
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
            $dateInterval = new \DateInterval('PT' . (int) $value . 'S');
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
        $regex = '/^' .
                 '(?:(?:(\d+(?:\.\d)?)?)h(?:[\s]*|$))?' .
                 '(?:(?:(\d+(?:\.\d)?)?)m(?:[\s]*|$))?' .
                 '(?:(?:(\d+(?:\.\d)?)?)s)?' .
                 '$/i';

        if (preg_match_all($regex, $time, $matches)) {
            return [
                'h' => $matches[1][0],
                'm' => $matches[2][0],
                's' => $matches[3][0],
            ];
        }

        // parse Column style (h:m:s)
        // make sure we have all parts when hours or minutes are omitted
        $parts = array_pad(explode(':', $time), -3, 0);

        return [
            'h' => (int)$parts[0],
            'm' => (int)$parts[1],
            's' => (int)$parts[2],
        ];
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
