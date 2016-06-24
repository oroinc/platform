<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

/**
 * Validates that the request data contains valid JSON.API object
 * that can be used to update the relationship.
 * This processor validates both "to-one" and "to-many" relationship data.
 */
class ValidateRequestData implements ProcessorInterface
{
    /** @var ChangeRelationshipContext */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ChangeRelationshipContext $context */

        $this->context = $context;
        try {
            $pointer = $this->buildPointer('', JsonApiDoc::DATA);
            $requestData = $context->getRequestData();
            if ($this->validateRequestData($requestData, $pointer)) {
                $data = $requestData[JsonApiDoc::DATA];
                if ($this->context->isCollection()) {
                    $this->validatePrimaryCollectionDataObject($data, $pointer);
                } else {
                    $this->validatePrimarySingleItemDataObject($data, $pointer);
                }
            }
        } finally {
            $this->context = null;
        }
    }

    /**
     * @param array  $data
     * @param string $pointer
     *
     * @return bool
     */
    protected function validateRequestData(array $data, $pointer)
    {
        if (!array_key_exists(JsonApiDoc::DATA, $data)) {
            $this->addError(
                $pointer,
                sprintf('The "%s" top-level section should exist', JsonApiDoc::DATA)
            );

            return false;
        }

        return true;
    }

    /**
     * @param mixed  $data
     * @param string $pointer
     */
    protected function validatePrimaryCollectionDataObject($data, $pointer)
    {
        if (!is_array($data) || ArrayUtil::isAssoc($data)) {
            $this->addError(
                $pointer,
                'The list of resource identifier objects should be an array'
            );

            return;
        }

        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $this->addError(
                    $this->buildPointer($pointer, $key),
                    'The resource identifier object should be an object'
                );
            } else {
                $this->validateResourceObject($value, $this->buildPointer($pointer, $key));
            }
        }
    }

    /**
     * @param mixed  $data
     * @param string $pointer
     */
    protected function validatePrimarySingleItemDataObject($data, $pointer)
    {
        if (null === $data) {
            return;
        }
        if (!is_array($data)) {
            $this->addError(
                $pointer,
                'The resource identifier object should be NULL or an object'
            );

            return;
        }

        $this->validateResourceObject($data, $pointer);
    }

    /**
     * @param array  $data
     * @param string $pointer
     */
    protected function validateResourceObject(array $data, $pointer)
    {
        if (empty($data)) {
            $this->addError(
                $pointer,
                'The resource identifier object should be not empty object'
            );

            return;
        }
        if (!ArrayUtil::isAssoc($data)) {
            $this->addError(
                $pointer,
                'The resource identifier object should be an object'
            );

            return;
        }

        $this->validateRequiredStringProperty($data, JsonApiDoc::ID, $pointer);
        $this->validateRequiredStringProperty($data, JsonApiDoc::TYPE, $pointer);
    }

    /**
     * @param array  $data
     * @param string $property
     * @param string $pointer
     */
    protected function validateRequiredStringProperty(array $data, $property, $pointer)
    {
        if (!array_key_exists($property, $data)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property is required', $property)
            );
        } elseif (!is_string($data[$property]) && null !== $data[$property]) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should be a string', $property)
            );
        } elseif ('' === $data[$property] || null === $data[$property]) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should not be empty', $property)
            );
        }
    }

    /**
     * @param string $parentPath
     * @param string $property
     *
     * @return string
     */
    protected function buildPointer($parentPath, $property)
    {
        return $parentPath . '/' . $property;
    }

    /**
     * @param string $pointer
     * @param string $message
     */
    protected function addError($pointer, $message)
    {
        $error = Error::createValidationError(Constraint::REQUEST_DATA, $message)
            ->setSource(ErrorSource::createByPointer($pointer));

        $this->context->addError($error);
    }
}
