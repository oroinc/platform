<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\ProcessIncludedEntities as BaseProcessIncludedEntities;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Validates and fill included entities.
 */
class ProcessIncludedEntities extends BaseProcessIncludedEntities
{
    /**
     * {@inheritdoc}
     */
    protected function fixErrorPath(Error $error, $entityPath)
    {
        $errorSource = $error->getSource();
        if (null === $errorSource) {
            $error->setSource(ErrorSource::createByPropertyPath($entityPath));
        } else {
            $pointer = $errorSource->getPointer();
            if ($pointer && 0 === strpos($pointer, '/' . JsonApiDoc::DATA)) {
                $errorSource->setPointer($entityPath . substr($pointer, strlen(JsonApiDoc::DATA) + 1));
            } else {
                $propertyPath = $errorSource->getPropertyPath();
                if ($propertyPath) {
                    $propertyPath = str_replace('/', '.', $entityPath) . '.' . $propertyPath;
                    if (0 === strpos($propertyPath, '.')) {
                        $propertyPath = substr($propertyPath, 1);
                    }
                    $errorSource->setPropertyPath($propertyPath);
                }
            }
        }
    }
}
