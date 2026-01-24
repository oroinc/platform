<?php

namespace Oro\Bundle\CacheBundle\Action\Transformer;

/**
 * Transforms `DateTime` objects to and from cron schedule format strings.
 *
 * This transformer converts `DateTime` instances into cron expression format (minute hour day month *)
 * for use in scheduled cache invalidation operations. It also provides reverse transformation
 * to parse cron format strings back into `DateTime` objects, enabling bidirectional conversion
 * between human-readable `DateTime` values and cron-compatible schedule expressions.
 */
class DateTimeToCronFormatTransformer implements DateTimeToStringTransformerInterface
{
    /**
     * @param \DateTime $dateTime
     *
     * @return string
     */
    #[\Override]
    public function transform(\DateTime $dateTime)
    {
        return sprintf(
            '%s %s %s %s *',
            $dateTime->format('i'),
            $dateTime->format('H'),
            $dateTime->format('d'),
            $dateTime->format('m')
        );
    }

    /**
     * @param string $string
     *
     * @return \DateTime|null
     */
    #[\Override]
    public function reverseTransform($string)
    {
        $dateTime = \DateTime::createFromFormat('i H d m *', $string, new \DateTimeZone('UTC'));

        if ($dateTime === false) {
            return null;
        }

        return $dateTime;
    }
}
