<?php

namespace Oro\Bundle\GaufretteBundle\Exception;

use Gaufrette\Exception;

/**
 * This exception is thrown when some error occurred during the flushing data to a stream.
 */
class FlushFailedException extends \RuntimeException implements Exception
{
}
