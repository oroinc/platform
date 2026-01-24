<?php

namespace Oro\Bundle\ThemeBundle\Exception;

/**
 * Thrown when a requested theme cannot be found in the application.
 *
 * This exception is raised when the theme system attempts to load or access a theme that does not exist
 * or is not available in the current application configuration.
 */
class ThemeNotFoundException extends \LogicException implements ThemeException
{
}
