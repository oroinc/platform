<?php

namespace Oro\Bundle\LayoutBundle\Exception;

/**
 * Thrown when a block view variable has an unexpected or invalid type.
 *
 * This exception is raised during block view rendering when a variable
 * does not match the expected type, indicating a configuration or data
 * preparation error.
 */
class UnexpectedBlockViewVarTypeException extends \Exception
{
}
