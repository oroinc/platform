<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractErrorCompleter;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

class ErrorCompleter extends AbstractErrorCompleter
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
        $this->completeSource($error, $metadata);
    }

    /**
     * @param Error               $error
     * @param EntityMetadata|null $metadata
     */
    public function completeSource(Error $error, EntityMetadata $metadata = null)
    {
        $source = $error->getSource();
        if (null !== $source && !$source->getPointer() && $source->getPropertyPath()) {
            $propertyPath = $source->getPropertyPath();
            if (!$metadata) {
                $error->setDetail($this->appendSourceToMessage($error->getDetail(), $propertyPath));
                $error->setSource(null);
            } else {
                $pointer = [];
                if (in_array($propertyPath, $metadata->getIdentifierFieldNames(), true)) {
                    $pointer[] = JsonApiDoc::ID;
                } elseif (array_key_exists($propertyPath, $metadata->getFields())) {
                    $pointer = [JsonApiDoc::ATTRIBUTES, $propertyPath];
                } else {
                    $parts = explode('.', $propertyPath);
                    if (array_key_exists($parts[0], $metadata->getAssociations())) {
                        $pointer = [JsonApiDoc::RELATIONSHIPS, $parts[0], JsonApiDoc::DATA];
                        if (count($parts) > 1) {
                            $pointer[] = $parts[1];
                        }
                    } else {
                        $error->setDetail($this->appendSourceToMessage($error->getDetail(), $propertyPath));
                        $error->setSource(null);
                    }
                }
                if (!empty($pointer)) {
                    $source->setPointer(sprintf('/%s/%s', JsonApiDoc::DATA, implode('/', $pointer)));
                    $source->setPropertyPath(null);
                }
            }
        }
    }

    /**
     * @param string $message
     * @param string $source
     *
     * @return string
     */
    protected function appendSourceToMessage($message, $source)
    {
        if (!$this->endsWith($message, '.')) {
            $message .= '.';
        }

        return sprintf('%s Source: %s.', $message, $source);
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
