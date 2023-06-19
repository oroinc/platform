<?php

namespace Oro\Component\MessageQueue\Consumption\Exception;

/**
 * Exception that is thrown when the consumption is interrupted
 */
class ConsumptionInterruptedException extends \LogicException implements ExceptionInterface
{
}
