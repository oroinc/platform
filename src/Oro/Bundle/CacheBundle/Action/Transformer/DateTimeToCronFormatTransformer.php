<?php

namespace Oro\Bundle\CacheBundle\Action\Transformer;

class DateTimeToCronFormatTransformer implements DateTimeToStringTransformerInterface
{
    /**
     * @param \DateTime $dateTime
     *
     * @return string
     */
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
    public function reverseTransform($string)
    {
        $dateTime = \DateTime::createFromFormat('i H d m *', $string, new \DateTimeZone('UTC'));

        if ($dateTime === false) {
            return null;
        }

        return $dateTime;
    }
}
