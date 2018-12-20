<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Exception\ExceptionInterface as ApiException;
use Oro\Bundle\ApiBundle\Exception\ValidationExceptionInterface;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * The default implementation of extractor that retrieves information from an exception object.
 */
class ExceptionTextExtractor implements ExceptionTextExtractorInterface
{
    /** @var bool */
    private $debug;

    /** @var TranslatorInterface */
    private $translator;

    /** @var string[] */
    private $safeExceptions;

    /**
     * @param bool                $debug
     * @param TranslatorInterface $translator
     * @param string[]            $safeExceptions
     */
    public function __construct($debug, TranslatorInterface $translator, array $safeExceptions)
    {
        $this->debug = $debug;
        $this->translator = $translator;
        $this->safeExceptions = $safeExceptions;
        $this->safeExceptions[] = ApiException::class;
        $this->safeExceptions[] = HttpExceptionInterface::class;
        $this->safeExceptions[] = AccessDeniedException::class;
        $this->safeExceptions[] = AuthenticationException::class;
        $this->safeExceptions[] = ForbiddenException::class;
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
        if ($underlyingException instanceof AccessDeniedException
            || $underlyingException instanceof ForbiddenException
            || $underlyingException instanceof AuthenticationException
        ) {
            return Response::HTTP_FORBIDDEN;
        }

        /**
         * check for ValidationExceptionInterface should be at the end,
         * because some exceptions can implement several interfaces and
         * ValidationExceptionInterface should have lowest priority
         * e.g. an exception can implement both ValidationExceptionInterface and HttpExceptionInterface,
         * but the status code for it should be retrieved from HttpExceptionInterface
         */
        if ($underlyingException instanceof ValidationExceptionInterface) {
            return Response::HTTP_BAD_REQUEST;
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
        $exception = ExceptionUtil::getProcessorUnderlyingException($exception);
        $exceptionClass = \get_class($exception);
        if ($exception instanceof AuthenticationException) {
            $exceptionClass = AuthenticationException::class;
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
            if (\substr($text, -1) !== '.') {
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
    private function isSafeException(\Exception $exception)
    {
        foreach ($this->safeExceptions as $class) {
            if (\is_a($exception, $class)) {
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
    private function getSafeExceptionText(\Exception $exception)
    {
        if ($exception instanceof ForbiddenException) {
            return $exception->getReason();
        }
        if ($exception instanceof AuthenticationException) {
            return $this->translator->trans($exception->getMessageKey(), $exception->getMessageData());
        }

        return $exception->getMessage();
    }
}
