<?php

namespace Oro\Bundle\EmailBundle\Exception;

/**
 * This exception is EmailBundle's version of standard InvalidArgumentException which makes it possible to catch
 * only EmailBundle related exceptions.
 */
class InvalidArgumentException extends \InvalidArgumentException
{
}
