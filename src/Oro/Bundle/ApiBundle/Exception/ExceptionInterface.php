<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * Marker interface to denote exceptions thrown from the Data API.
 * Use exceptions implement this interface if you want to return
 * an exception message as an error detail.
 * @see Oro\Bundle\ApiBundle\Model\Error::getDetail
 * @see Oro\Bundle\ApiBundle\Request\ExceptionTextExtractor::getExceptionText
 */
interface ExceptionInterface
{
}
