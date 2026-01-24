<?php

namespace Oro\Bundle\EntityBundle\Exception\Fallback;

/**
 * Thrown when a fallback field configuration is missing or not found.
 *
 * This exception indicates that a field expected to have fallback configuration
 * does not have the required configuration defined.
 */
class FallbackFieldConfigurationMissingException extends \Exception
{
}
