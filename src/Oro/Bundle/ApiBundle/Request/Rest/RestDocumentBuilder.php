<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;

class RestDocumentBuilder extends AbstractDocumentBuilder
{
    /**
     * {@inheritdoc}
     */
    public function getDocument()
    {
        $result = null;
        if (array_key_exists(self::DATA, $this->result)) {
            $result = $this->result[self::DATA];
        } elseif (array_key_exists(self::ERRORS, $this->result)) {
            $result = $this->result[self::ERRORS];
        }
        if (null === $result) {
            $result = [];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function transformObjectToArray($object, EntityMetadata $metadata = null)
    {
        return $this->objectAccessor->toArray($object);
    }

    /**
     * {@inheritdoc}
     */
    protected function transformErrorToArray(Error $error)
    {
        $result = [];

        if ($error->getCode()) {
            $result['code'] = (string)$error->getCode();
        }
        if ($error->getTitle()) {
            $result['title'] = $error->getTitle();
        }
        if ($error->getDetail()) {
            $result['detail'] = $error->getDetail();
        }
        $source = $error->getSource();
        if ($source) {
            if ($source->getPointer()) {
                $result['source'] = $source->getPointer();
            } elseif ($source->getParameter()) {
                $result['source'] = $source->getParameter();
            } elseif ($source->getPropertyPath()) {
                $result['source'] = $source->getPropertyPath();
            }
        }

        return $result;
    }
}
