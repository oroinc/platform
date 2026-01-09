<?php

namespace Oro\Component\Layout\Exception;

/**
 * Thrown when a circular reference is detected in layout item dependencies or imports.
 */
class CircularReferenceException extends \Exception
{
}
