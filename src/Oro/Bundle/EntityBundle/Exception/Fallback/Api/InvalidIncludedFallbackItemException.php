<?php

namespace Oro\Bundle\EntityBundle\Exception\Fallback\Api;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;

class InvalidIncludedFallbackItemException extends \Exception
{
    /**
     * @param string $itemId
     */
    public function __construct($itemId)
    {
        $message = sprintf(
            "Invalid entity fallback value provided for the included value with id '%s'. Please provide a correct id, and an attribute section with either a '%s' identifier, an '%s' or '%s'",
            $itemId,
            EntityFieldFallbackValue::FALLBACK_PARENT_FIELD,
            EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD,
            EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD
        );

        parent::__construct($message);
    }
}
