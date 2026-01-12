<?php

namespace Oro\Component\Action\Exception;

/**
 * Thrown when action or condition assembly fails.
 *
 * This exception is raised when the {@see ActionAssembler} or condition assembler encounters
 * invalid configuration or cannot construct the requested action or condition instance.
 */
class AssemblerException extends \Exception
{
}
