<?php

namespace Oro\Bundle\EntityBundle\Fallback\Provider;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;

interface EntityFallbackProviderInterface
{
    /**
     * @param object $object The object for which we are getting the fallback value
     * @param string $objectFieldName The field name on $object which is holding the fallback value
     * @param EntityFieldFallbackValue $objectFallbackValue The actual EntityFieldFallbackValue object from the $object
     * @param array $fallbackConfig The $objectFieldName property's fallback configuration
     *
     * @return mixed
     *
     */
    public function getFallbackHolderEntity(
        $object,
        $objectFieldName,
        EntityFieldFallbackValue $objectFallbackValue,
        $fallbackConfig
    );

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getId();
}
