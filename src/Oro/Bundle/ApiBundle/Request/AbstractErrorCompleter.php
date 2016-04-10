<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Model\Error;

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
            if (null !== $error->getInnerException()) {
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
        if (null === $error->getDetail() && null !== $error->getInnerException()) {
            $detail = $this->exceptionTextExtractor->getExceptionText($error->getInnerException());
            if (null !== $detail) {
                $error->setDetail($detail);
            }
        }
    }
}
