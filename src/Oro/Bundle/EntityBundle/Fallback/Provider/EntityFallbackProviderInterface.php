<?php

namespace Oro\Bundle\EntityBundle\Fallback\Provider;

interface EntityFallbackProviderInterface
{
    /**
     * @param object $object The object for which we are getting the fallback value
     * @param string $objectFieldName The field name on $object which is holding the fallback value
     * @return mixed
     */
    public function getFallbackHolderEntity($object, $objectFieldName);

    /**
     * @param object $object
     * @param string $objectFieldName
     * @return bool
     */
    public function isFallbackSupported($object, $objectFieldName);

    /**
     * @return string
     */
    public function getFallbackLabel();

    /**
     * @return string|null
     */
    public function getFallbackEntityClass();
}
