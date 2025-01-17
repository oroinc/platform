<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorMetaProperty;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * The base class for different kind of error completers.
 */
abstract class AbstractErrorCompleter implements ErrorCompleterInterface
{
    private ErrorTitleOverrideProvider $errorTitleOverrideProvider;
    private ExceptionTextExtractorInterface $exceptionTextExtractor;
    protected ValueNormalizer $valueNormalizer;

    public function __construct(
        ErrorTitleOverrideProvider $errorTitleOverrideProvider,
        ExceptionTextExtractorInterface $exceptionTextExtractor,
        ValueNormalizer $valueNormalizer
    ) {
        $this->errorTitleOverrideProvider = $errorTitleOverrideProvider;
        $this->exceptionTextExtractor = $exceptionTextExtractor;
        $this->valueNormalizer = $valueNormalizer;
    }

    protected function completeStatusCode(Error $error): void
    {
        if (null === $error->getStatusCode() && null !== $error->getInnerException()) {
            $statusCode = $this->exceptionTextExtractor->getExceptionStatusCode($error->getInnerException());
            if (null !== $statusCode) {
                $error->setStatusCode($statusCode);
            }
        }
    }

    protected function completeCode(Error $error): void
    {
        if (null === $error->getCode() && null !== $error->getInnerException()) {
            $code = $this->exceptionTextExtractor->getExceptionCode($error->getInnerException());
            if (null !== $code) {
                $error->setCode($code);
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function completeTitle(Error $error): void
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
                if (null !== $statusCode && \array_key_exists($statusCode, Response::$statusTexts)) {
                    $error->setTitle(strtolower(Response::$statusTexts[$statusCode]));
                }
            }
        }
        if ($error->getTitle()) {
            $title = $this->errorTitleOverrideProvider->getSubstituteErrorTitle($error->getTitle());
            if ($title) {
                $error->setTitle($title);
            }
        }
    }

    protected function completeDetail(Error $error): void
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

    protected function completeMetaProperties(Error $error, RequestType $requestType): void
    {
        $metaProperties = $error->getMetaProperties();
        if ($metaProperties) {
            foreach ($metaProperties as $metaProperty) {
                $this->completeMetaProperty($metaProperty, $requestType);
            }
        }
    }

    protected function completeMetaProperty(ErrorMetaProperty $metaProperty, RequestType $requestType): void
    {
        $value = $metaProperty->getValue();
        if (null === $value) {
            return;
        }

        $dataType = $metaProperty->getDataType();
        $isArrayAllowed = false;
        if (str_ends_with($dataType, '[]')) {
            $dataType = substr($dataType, 0, -2);
            $isArrayAllowed = true;
        }

        $metaProperty->setValue($this->valueNormalizer->normalizeValue(
            $value,
            $dataType,
            $requestType,
            $isArrayAllowed
        ));
    }

    protected function isConfigFilterConstraintViolation(Error $error): bool
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
