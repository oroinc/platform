<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\HttpFoundation\Response;

/**
 * This trait can be used to compute a HTTP response status code
 * when several errors with different status codes occur.
 */
trait ErrorResponseStatusCodeTrait
{
    private function computeResponseStatusCode(array $statusCodes): int
    {
        $groupedCodes = [];
        foreach ($statusCodes as $code) {
            /** @var int $groupCode */
            $groupCode = (int)floor($code / 100) * 100;
            if (!\array_key_exists($groupCode, $groupedCodes)
                || !\in_array($code, $groupedCodes[$groupCode], true)
            ) {
                $groupedCodes[$groupCode][] = $code;
            }
        }

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        if (!empty($groupedCodes)) {
            $maxGroup = max(array_keys($groupedCodes));
            $statusCode = $maxGroup;
            if (\count($groupedCodes[$maxGroup]) === 1) {
                $statusCode = reset($groupedCodes[$maxGroup]);
            }
        }

        return $statusCode;
    }
}
