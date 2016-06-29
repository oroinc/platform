<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * This exception thrown if an error which can only be found on runtime occurs.
 * Use this exception instead of \RuntimeException if you want to return
 * an exception message as an error detail.
 * @see Oro\Bundle\ApiBundle\Model\Error::getDetail
 * @see Oro\Bundle\ApiBundle\Request\ExceptionTextExtractor::getExceptionText
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
}
