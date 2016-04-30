<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
        return ValueNormalizerUtil::humanizeClassName(
            get_class(ExceptionUtil::getProcessorUnderlyingException($exception)),
            'Exception'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionText(\Exception $exception)
    {
        if ($this->debug) {
            return $exception->getMessage();
        }

        $underlyingException = ExceptionUtil::getProcessorUnderlyingException($exception);
        if ($this->isSafeException($underlyingException)) {
            return $underlyingException->getMessage();
        }

        return null;
    }

    /**
     * @param \Exception $exception
     *
     * @return bool
     */
    protected function isSafeException(\Exception $exception)
    {
        foreach ($this->safeExceptions as $class) {
            if (is_a($exception, $class)) {
                return true;
            }
        }

        return false;
    }
}
