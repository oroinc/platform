<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\HttpFoundation\Response;

/**
 * This trait can be used to check whether HTTP response that represents client or server error
 * should not have a content.
 */
trait ErrorStatusCodesWithoutContentTrait
{
    /**
     * Indicates whether HTTP response with the given status code should not have a content.
     * It is supposed that this method will be used only for status codes greater or equal to 400 (Bad Request).
     *
     * @param int $statusCode
     *
     * @return bool
     */
    private function isErrorResponseWithoutContent(int $statusCode): bool
    {
        return Response::HTTP_METHOD_NOT_ALLOWED === $statusCode;
    }
}
