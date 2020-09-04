<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Exception\ExceptionInterface as ApiException;
use Oro\Bundle\ApiBundle\Exception\ValidationExceptionInterface;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /** @var string[] */
    private $safeExceptionExclusions;

    /**
     * @param bool                $debug
     * @param TranslatorInterface $translator
     * @param string[]            $safeExceptions
     * @param string[]            $safeExceptionExclusions
     */
    public function __construct(
        $debug,
        TranslatorInterface $translator,
        array $safeExceptions,
        array $safeExceptionExclusions = []
    ) {
        $this->debug = $debug;
        $this->translator = $translator;
        $this->safeExceptions = $safeExceptions;
        $this->safeExceptionExclusions = $safeExceptionExclusions;
        $this->safeExceptions[] = ApiException::class;
        $this->safeExceptions[] = HttpExceptionInterface::class;
        $this->safeExceptions[] = AccessDeniedException::class;
        $this->safeExceptions[] = AuthenticationException::class;
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
        } elseif ($exception instanceof AccessDeniedException
            || $exception instanceof AccessDeniedHttpException
        ) {
            $exceptionClass = AccessDeniedException::class;
        }

        return ValueNormalizerUtil::humanizeClassName($exceptionClass, 'Exception');
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
        $isSafe = false;
        foreach ($this->safeExceptions as $class) {
            if (\is_a($exception, $class)) {
                $isSafe = true;
                break;
            }
        }
        if ($isSafe) {
            foreach ($this->safeExceptionExclusions as $exclusionClass) {
                if (\is_a($exception, $exclusionClass)) {
                    $isSafe = false;
                    break;
                }
            }
        }

        return $isSafe;
    }

    /**
     * @param \Exception $exception
     *
     * @return string
     */
    private function getSafeExceptionText(\Exception $exception)
    {
        if ($exception instanceof AccessDeniedException) {
            return $exception->getMessage();
        }
        if ($exception instanceof AuthenticationException) {
            return $this->translator->trans(
                $exception->getMessageKey(),
                $exception->getMessageData(),
                'security'
            );
        }

        return $exception->getMessage();
    }
}
