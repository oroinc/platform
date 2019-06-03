<?php

namespace Oro\Component\MessageQueue\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;

/**
 * If message body not valid throw exception and reject message
 */
class InvalidArgumentException extends \InvalidArgumentException implements RejectMessageExceptionInterface
{
}
