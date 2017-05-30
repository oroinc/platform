<?php

namespace Oro\Bundle\CacheBundle\Action\Transformer;

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
