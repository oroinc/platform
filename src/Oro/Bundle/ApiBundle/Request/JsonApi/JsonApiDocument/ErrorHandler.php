<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocument;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;

class ErrorHandler
{
    const CODE              = 'code';
    const DETAIL            = 'detail';
    const TITLE             = 'title';
    const SOURCE            = 'source';
    const POINTER           = 'pointer';
    const POINTER_DELIMITER = '/';

    /**
     * Return JsonAPI representation error
     *
     * @param Error          $error
     * @param EntityMetadata $metadata
     *
     * @return array
     */
    public function handleError(Error $error, EntityMetadata $metadata = null)
    {
        $result = [];
        if ($error->getStatusCode()) {
            $result[self::CODE] = (string)$error->getStatusCode();
        }
        if ($error->getDetail()) {
            $result[self::DETAIL] = $error->getDetail();
        }
        if ($error->getTitle()) {
            $result[self::TITLE] = $error->getTitle();
        }
        if ($error->getPropertyName()) {
            $this->processPointer($result, $error, $metadata);
        }

        return $result;
    }

    /**
     * @param array          $result
     * @param Error          $error
     * @param EntityMetadata $metadata
     */
    protected function processPointer(&$result, Error $error, EntityMetadata $metadata = null)
    {
        $property = $error->getPropertyName();

        if (!$metadata) {
            $result[self::DETAIL] = sprintf(
                '%s Source: %s',
                $result[self::DETAIL],
                $property
            );

            return;
        }

        $pointer = [];
        if (in_array($property, $metadata->getIdentifierFieldNames(), true)) {
            $pointer[] = JsonApiDocumentBuilder::ID;
        } elseif (array_key_exists($property, $metadata->getFields())) {
            $pointer = [JsonApiDocumentBuilder::ATTRIBUTES, $property];
        } elseif (array_key_exists($property, $metadata->getAssociations())) {
            $pointer = [JsonApiDocumentBuilder::RELATIONSHIPS, $property];
        } else {
            $result[self::DETAIL] = sprintf(
                '%s Source: %s',
                $result[self::DETAIL],
                $property
            );
        }

        if (count($pointer) !== 0) {
            array_unshift($pointer, JsonApiDocumentBuilder::DATA);
            $result[self::SOURCE][self::POINTER] = $this->getPointerString($pointer);
        }
    }

    /**
     * @param array $pointerParts
     *
     * @return string
     */
    protected function getPointerString(array $pointerParts)
    {
        return self::POINTER_DELIMITER . implode(self::POINTER_DELIMITER, $pointerParts);
    }
}
