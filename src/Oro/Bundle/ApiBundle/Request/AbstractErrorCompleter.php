<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * The base class for different kind of error completers.
 */
abstract class AbstractErrorCompleter implements ErrorCompleterInterface
{
    /** @var ExceptionTextExtractorInterface */
    protected $exceptionTextExtractor;

    /**
     * @param ExceptionTextExtractorInterface $exceptionTextExtractor
     */
    public function __construct(ExceptionTextExtractorInterface $exceptionTextExtractor)
    {
        $this->exceptionTextExtractor = $exceptionTextExtractor;
    }

    /**
     * @param Error $error
     */
    protected function completeStatusCode(Error $error)
    {
        if (null === $error->getStatusCode() && null !== $error->getInnerException()) {
            $statusCode = $this->exceptionTextExtractor->getExceptionStatusCode($error->getInnerException());
            if (null !== $statusCode) {
                $error->setStatusCode($statusCode);
            }
        }
    }

    /**
     * @param Error $error
     */
    protected function completeCode(Error $error)
    {
        if (null === $error->getCode() && null !== $error->getInnerException()) {
            $code = $this->exceptionTextExtractor->getExceptionCode($error->getInnerException());
            if (null !== $code) {
                $error->setCode($code);
            }
        }
    }

    /**
     * @param Error $error
     */
    protected function completeTitle(Error $error)
    {
        if (null === $error->getTitle()) {
            if ($this->isConfigFilterConstraintViolation($error)) {
                $error->setTitle(Constraint::FILTER);
            } elseif (null !== $error->getInnerException()) {
                $title = $this->exceptionTextExtractor->getExceptionType($error->getInnerException());
                if (null !== $title) {
                    $error->setTitle($title);
                }
            }
            if (null === $error->getTitle()) {
                $statusCode = $error->getStatusCode();
                if (null !== $statusCode && array_key_exists($statusCode, Response::$statusTexts)) {
                    $error->setTitle(Response::$statusTexts[$statusCode]);
                }
            }
        }
    }

    /**
     * @param Error $error
     */
    protected function completeDetail(Error $error)
    {
        if (null === $error->getDetail()) {
            if ($this->isConfigFilterConstraintViolation($error)) {
                $error->setDetail('The filter is not supported.');
            } elseif (null !== $error->getInnerException()) {
                $detail = $this->exceptionTextExtractor->getExceptionText($error->getInnerException());
                if (null !== $detail) {
                    $error->setDetail($detail);
                }
            }
        }
    }

    /**
     * @param Error $error
     *
     * @return bool
     */
    protected function isConfigFilterConstraintViolation(Error $error)
    {
        if (null === $error->getInnerException()) {
            return false;
        }

        $underlyingException = ExceptionUtil::getProcessorUnderlyingException($error->getInnerException());

        return
            $underlyingException instanceof NotSupportedConfigOperationException
            && (
                ExpandRelatedEntitiesConfigExtra::NAME === $underlyingException->getOperation()
                || FilterFieldsConfigExtra::NAME === $underlyingException->getOperation()
            );
    }
}
