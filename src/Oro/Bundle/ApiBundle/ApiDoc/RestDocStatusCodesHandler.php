<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;

/**
 * Adds status codes to ApiDoc annotation.
 */
class RestDocStatusCodesHandler
{
    /**
     * @param ApiDoc            $annotation
     * @param StatusCodesConfig $statusCodes
     */
    public function handle(ApiDoc $annotation, StatusCodesConfig $statusCodes)
    {
        $codes = $statusCodes->getCodes();
        \ksort($codes);
        foreach ($codes as $statusCode => $code) {
            if (!$code->isExcluded()) {
                $annotation->addStatusCode($statusCode, $code->getDescription());
            }
        }
    }
}
