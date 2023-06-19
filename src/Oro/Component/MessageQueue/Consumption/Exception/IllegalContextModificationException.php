<?php

namespace Oro\Component\MessageQueue\Consumption\Exception;

/**
 * Exception that is thrown when the context is modified in the wrong way
 */
class IllegalContextModificationException extends \LogicException implements ExceptionInterface
{
}
