<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

class RestDocStatusCodesHandler
{
    /**
     * @param ApiDoc                 $annotation
     * @param EntityDefinitionConfig $config
     */
    public function handle(ApiDoc $annotation, EntityDefinitionConfig $config)
    {
        $statusCodes = $config->getStatusCodes();
        if (null !== $statusCodes) {
            $codes = $statusCodes->getCodes();
            foreach ($codes as $statusCode => $code) {
                if (!$code->isExcluded()) {
                    $annotation->addStatusCode($statusCode, $code->getDescription());
                }
            }
        }
    }
}
