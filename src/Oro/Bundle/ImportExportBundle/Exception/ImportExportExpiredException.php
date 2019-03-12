<?php

namespace Oro\Bundle\ImportExportBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Exception indicates that the requested resource no longer exists and will not be available in the future
 */
class ImportExportExpiredException extends HttpException
{
    /**
     * @param int $statusCode
     * @param string $message
     * @param \Exception|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct(
        $statusCode = Response::HTTP_GONE,
        $message = 'The link has expired.',
        \Exception $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}
