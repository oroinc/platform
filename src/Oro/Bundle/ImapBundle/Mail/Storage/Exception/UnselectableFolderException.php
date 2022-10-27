<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage\Exception;

use Laminas\Mail\Storage\Exception\RuntimeException;

/**
 * An exception that is thrown when the folder can't be selected.
 */
class UnselectableFolderException extends RuntimeException
{
}
