<?php

namespace Oro\Bundle\EntityBundle\Fallback\Provider;

/**
 * Defines the contract for entity fallback value providers.
 *
 * Implementations of this interface provide fallback values for entity fields
 * when the field value is not explicitly set. They determine which entity holds
 * the fallback value and provide labels and entity class information for the fallback.
 */
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
