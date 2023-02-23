<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Exception\ExceptionInterface as ApiException;
use Oro\Bundle\ApiBundle\Exception\ValidationExceptionInterface;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The default implementation of extractor that retrieves information from an exception object.
 */
class ExceptionTextExtractor implements ExceptionTextExtractorInterface
{
    private bool $debug;
    private TranslatorInterface $translator;
    /** @var string[] */
    private array $safeExceptions;
    /** @var string[] */
    private array $safeExceptionExclusions;

    /**
     * @param bool                $debug
     * @param TranslatorInterface $translator
     * @param string[]            $safeExceptions
     * @param string[]            $safeExceptionExclusions
     */
    public function __construct(
        bool $debug,
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
     * {@inheritDoc}
     */
    public function getExceptionStatusCode(\Exception $exception): ?int
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
     * {@inheritDoc}
     */
    public function getExceptionCode(\Exception $exception): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getExceptionType(\Exception $exception): ?string
    {
        $underlyingException = ExceptionUtil::getProcessorUnderlyingException($exception);

        if (\get_class($underlyingException) === HttpException::class) {
            $statusCode = $underlyingException->getStatusCode();
            if (\array_key_exists($statusCode, Response::$statusTexts)) {
                return strtolower(Response::$statusTexts[$statusCode]) . ' http exception';
            }
        }

        $underlyingExceptionClass = \get_class($underlyingException);
        if ($underlyingException instanceof AuthenticationException) {
            $underlyingExceptionClass = AuthenticationException::class;
        } elseif ($underlyingException instanceof AccessDeniedException
            || $underlyingException instanceof AccessDeniedHttpException
        ) {
            $underlyingExceptionClass = AccessDeniedException::class;
        }

        return ValueNormalizerUtil::humanizeClassName($underlyingExceptionClass, 'Exception');
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getExceptionText(\Exception $exception): ?string
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
            if (!str_ends_with($text, '.')) {
                $text .= '.';
            }
            if ($underlyingException !== $exception && $exception instanceof ExecutionFailedException) {
                $processorPath = $exception->getProcessorId();
                $e = $exception->getPrevious();
                while ($e instanceof ExecutionFailedException) {
                    $processorPath .= '->' . $e->getProcessorId();
                    $e = $e->getPrevious();
                }
                $text .= ' Processor: ' . $processorPath . '.';
            }
        }

        return $text;
    }

    private function isSafeException(\Exception $exception): bool
    {
        $isSafe = false;
        foreach ($this->safeExceptions as $class) {
            if (is_a($exception, $class)) {
                $isSafe = true;
                break;
            }
        }
        if ($isSafe) {
            foreach ($this->safeExceptionExclusions as $exclusionClass) {
                if (is_a($exception, $exclusionClass)) {
                    $isSafe = false;
                    break;
                }
            }
        }

        return $isSafe;
    }

    private function getSafeExceptionText(\Exception $exception): string
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
