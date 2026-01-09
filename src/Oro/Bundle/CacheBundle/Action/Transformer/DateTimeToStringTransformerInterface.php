<?php

namespace Oro\Bundle\CacheBundle\Action\Transformer;

/**
 * Defines the contract for bidirectional transformation between `DateTime` objects and string representations.
 *
 * Implementations of this interface are responsible for converting `DateTime` instances into
 * string format (such as cron expressions) and vice versa. This allows `DateTime` values to be
 * serialized into formats suitable for storage, transmission, or use in scheduled tasks,
 * while maintaining the ability to reconstruct `DateTime` objects from their string representations.
 */
interface DateTimeToStringTransformerInterface
{
    /**
     * @param \DateTime $dateTime
     *
     * @return string
     */
    public function transform(\DateTime $dateTime);

    /**
     * @param string $string
     *
     * @return \DateTime|null
     */
    public function reverseTransform($string);
}
