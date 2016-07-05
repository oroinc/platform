<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;

class RestDocumentBuilder extends AbstractDocumentBuilder
{
    const OBJECT_TYPE = 'entity';

    const ERROR_CODE   = 'code';
    const ERROR_TITLE  = 'title';
    const ERROR_DETAIL = 'detail';
    const ERROR_SOURCE = 'source';

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
        $result = $this->objectAccessor->toArray($object);
        if (!array_key_exists(self::OBJECT_TYPE, $result)) {
            $objectClass = $this->objectAccessor->getClassName($object);
            if ($objectClass) {
                $result[self::OBJECT_TYPE] = $objectClass;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function transformErrorToArray(Error $error)
    {
        $result = [];

        if ($error->getCode()) {
            $result[self::ERROR_CODE] = (string)$error->getCode();
        }
        if ($error->getTitle()) {
            $result[self::ERROR_TITLE] = $error->getTitle();
        }
        if ($error->getDetail()) {
            $result[self::ERROR_DETAIL] = $error->getDetail();
        }
        $source = $error->getSource();
        if ($source) {
            if ($source->getPointer()) {
                $result[self::ERROR_SOURCE] = $source->getPointer();
            } elseif ($source->getParameter()) {
                $result[self::ERROR_SOURCE] = $source->getParameter();
            } elseif ($source->getPropertyPath()) {
                $result[self::ERROR_SOURCE] = $source->getPropertyPath();
            }
        }

        return $result;
    }
}
