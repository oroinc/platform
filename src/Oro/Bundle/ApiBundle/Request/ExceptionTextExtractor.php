<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Exception\ExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException as PropertyAccessException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;

class ExceptionTextExtractor implements ExceptionTextExtractorInterface
{
    /** @var bool */
    protected $debug;

    /** @var string[] */
    protected $safeExceptions;

    /**
     * @param bool     $debug
     * @param string[] $safeExceptions
     */
    public function __construct($debug, $safeExceptions)
    {
        $this->debug = $debug;
        $this->safeExceptions = $safeExceptions;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionStatusCode(\Exception $exception)
    {
        $underlyingException = ExceptionUtil::getProcessorUnderlyingException($exception);
        if ($underlyingException instanceof HttpExceptionInterface) {
            return $underlyingException->getStatusCode();
        }
        if ($underlyingException instanceof AccessDeniedException) {
            return $underlyingException->getCode();
        }
        if ($underlyingException instanceof ForbiddenException) {
            return Response::HTTP_FORBIDDEN;
        }
        if ($underlyingException instanceof PropertyAccessException) {
            return Response::HTTP_METHOD_NOT_ALLOWED;
        }
        if ($underlyingException instanceof ActionNotAllowedException) {
            return Response::HTTP_METHOD_NOT_ALLOWED;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionCode(\Exception $exception)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionType(\Exception $exception)
    {
        $underlyingException = ExceptionUtil::getProcessorUnderlyingException($exception);
        $exceptionClass = get_class($underlyingException);
        if ($underlyingException instanceof ForbiddenException) {
            $exceptionClass = ForbiddenException::class;
        }

        return ValueNormalizerUtil::humanizeClassName($exceptionClass, 'Exception');
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionText(\Exception $exception)
    {
        $underlyingException = ExceptionUtil::getProcessorUnderlyingException($exception);
        $text = null;
        if ($this->isSafeException($underlyingException)) {
            $text = $this->getSafeExceptionText($underlyingException);
        } elseif ($this->debug) {
            $text = $this->getSafeExceptionText($underlyingException);
            if ($text) {
                $text = '*DEBUG ONLY* ' . $text;
            }
        }
        if (null !== $text && !$text) {
            $text = null;
        }
        if (null !== $text) {
            if (substr($text, -1) !== '.') {
                $text .= '.';
            }
            if ($underlyingException !== $exception && $exception instanceof ExecutionFailedException) {
                $processorPath = $exception->getProcessorId();
                $e = $exception->getPrevious();
                while (null !== $e && $e instanceof ExecutionFailedException) {
                    $processorPath .= '->' . $e->getProcessorId();
                    $e = $e->getPrevious();
                }
                $text .= ' Processor: ' . $processorPath . '.';
            }
        }

        return $text;
    }

    /**
     * @param \Exception $exception
     *
     * @return bool
     */
    protected function isSafeException(\Exception $exception)
    {
        if ($exception instanceof ExceptionInterface
            || $exception instanceof ForbiddenException
        ) {
            return true;
        }

        foreach ($this->safeExceptions as $class) {
            if (is_a($exception, $class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Exception $exception
     *
     * @return string
     */
    protected function getSafeExceptionText(\Exception $exception)
    {
        if ($exception instanceof ForbiddenException) {
            return $exception->getReason();
        }

        return $exception->getMessage();
    }
}
