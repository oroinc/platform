<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms between time duration (string or \DateTime instance) and time duration string.
 * Straight transform will generate string from DateTime with the given generateFormat
 * or will just pass trough the value if it is already a string
 *
 * Reverse transform supports:
 * - JIRA style encoding (0.0h 0.0m 0.0s).
 *   Supports fractions (with dot delimiter) to the first digit.
 *   Result is rounded to the second. Any part could be omitted.
 * - Column style encoding h:m:s (0:0:0)
 *   Hours and minutes can be omitted, so 0:0 is treated min:sec, 0 as sec.
 *   Parts are converted to int, thus trailing non-digits are trimmed, while leading will
 *   result to 0's (1a:b2:3.5m => 1:0:3). Missing leading zeros are also valid (:1: => 0:1:0).
 * In both styles time parts are cumulative, so '1m 120s' (or '1:120') becomes 3 min.
 * Resulting \DateTime object is Unix epoch based (1970-01-01 0:0:0 UTC)
 */
class DurationToStringTransformer implements DataTransformerInterface
{
    /**
     * Format used for straight transform (\DateTime to string)
     * @var string
     */
    private $generateFormat;

    /**
     * @param string $generateFormat
     */
    public function __construct($generateFormat = 'H:i:s')
    {
        $this->generateFormat = $generateFormat;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        // do not transform if it is already a string
        if (is_string($value)) {
            return $value;
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTime or \DateTimeInterface.');
        }

        return $value->format($this->generateFormat);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value || (is_string($value) && '' === trim($value))) {
            return null;
        }

        // Do not transform if already a DateTime (for BC)
        if ($value instanceof \DateTime) {
            return $value;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $parts = $this->getTimeParts($value);

        $seconds = $parts['h'] * 3600 + $parts['m'] * 60 + $parts['s'];

        $dateTime = \DateTime::createFromFormat('U', round($seconds), new \DateTimeZone('UTC'));

        if (!$dateTime) {
            throw new TransformationFailedException('Failed to create a \DateTime instance.');
        }

        return $dateTime;
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
}
