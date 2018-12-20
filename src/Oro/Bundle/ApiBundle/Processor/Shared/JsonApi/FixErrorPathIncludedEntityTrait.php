<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Provides implementation of a method that can be used to
 * add the path to an included entity to an error path.
 */
trait FixErrorPathIncludedEntityTrait
{
    /**
     * @param Error  $error
     * @param string $entityPath
     */
    protected function fixIncludedEntityErrorPath(Error $error, string $entityPath): void
    {
        $errorSource = $error->getSource();
        if (null === $errorSource) {
            $error->setSource(ErrorSource::createByPointer($entityPath));
        } else {
            $pointer = $errorSource->getPointer();
            if ($pointer && 0 === \strpos($pointer, '/' . JsonApiDoc::DATA)) {
                $errorSource->setPointer($entityPath . \substr($pointer, \strlen(JsonApiDoc::DATA) + 1));
            } else {
                $propertyPath = $errorSource->getPropertyPath();
                if ($propertyPath) {
                    $propertyPath = \str_replace('/', '.', $entityPath) . '.' . $propertyPath;
                    if (0 === \strpos($propertyPath, '.')) {
                        $propertyPath = \substr($propertyPath, 1);
                    }
                    $errorSource->setPropertyPath($propertyPath);
                }
            }
        }
    }
}
