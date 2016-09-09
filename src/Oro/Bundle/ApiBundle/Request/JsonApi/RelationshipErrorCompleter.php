<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractErrorCompleter;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

class RelationshipErrorCompleter extends AbstractErrorCompleter
{
    /**
     * {@inheritdoc}
     */
    public function complete(Error $error, EntityMetadata $metadata = null)
    {
        $this->completeStatusCode($error);
        $this->completeCode($error);
        $this->completeTitle($error);
        $this->completeDetail($error);
        $this->completeSource($error);
    }

    /**
     * @param Error $error
     */
    public function completeSource(Error $error)
    {
        $source = $error->getSource();
        if (null !== $source && !$source->getPointer()) {
            $propertyPath = $source->getPropertyPath();
            if (null !== $propertyPath) {
                $pointer = empty($propertyPath)
                    ? sprintf('/%s', JsonApiDoc::DATA)
                    : sprintf('/%s/%s', JsonApiDoc::DATA, str_replace('.', '/', $propertyPath));
                $source->setPointer($pointer);
                $source->setPropertyPath(null);
            }
        }
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    protected function endsWith($haystack, $needle)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}
