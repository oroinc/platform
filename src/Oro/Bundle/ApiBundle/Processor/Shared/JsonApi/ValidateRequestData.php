<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Symfony\Component\HttpFoundation\Response;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;

class ValidateRequestData implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $requestData = $context->getRequestData();
        $pointer = [JsonApiDoc::DATA];
        if (!$this->validateDataObject($requestData, $pointer, $context)) {
            // we have no data in main object
            return;
        }

        $data = $requestData[JsonApiDoc::DATA];

        if ($this->validateResourceObject($data, $pointer, $context)) {
            if ($context->getId() !== $data[JsonApiDoc::ID]) {
                $this->addError(
                    $context,
                    array_merge($pointer, [JsonApiDoc::ID]),
                    sprintf(
                        'The \'%s\' parameters in request data and query sting should match each other',
                        JsonApiDoc::ID
                    )
                );
            }

            $dataClassName = ValueNormalizerUtil::convertToEntityClass(
                $this->valueNormalizer,
                $data[JsonApiDoc::TYPE],
                $context->getRequestType(),
                false
            );
            if ($dataClassName !== $context->getClassName()) {
                $this->addError(
                    $context,
                    array_merge($pointer, [JsonApiDoc::TYPE]),
                    sprintf(
                        'The \'%s\' parameters in request data and query sting should match each other',
                        JsonApiDoc::TYPE
                    )
                );

            }
        }

        if (!array_key_exists(JsonApiDoc::ATTRIBUTES, $data) && !array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)) {
            $this->addError(
                $context,
                $pointer,
                sprintf(
                    'The primary data object should contain \'%s\' or \'%s\' block',
                    JsonApiDoc::ATTRIBUTES,
                    JsonApiDoc::RELATIONSHIPS
                )
            );
        }

        if (array_key_exists(JsonApiDoc::ATTRIBUTES, $data)) {
            $this->validateAttributes(
                $data[JsonApiDoc::ATTRIBUTES],
                array_merge($pointer, [JsonApiDoc::ATTRIBUTES]),
                $context
            );
        }

        if (array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)) {
            $this->validateRelations(
                $data[JsonApiDoc::RELATIONSHIPS],
                array_merge($pointer, [JsonApiDoc::RELATIONSHIPS]),
                $context
            );
        }
    }

    /**
     * Validates relations block
     *
     * @param array            $data
     * @param array            $pointer
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected function validateRelations($data, array $pointer, ContextInterface $context)
    {
        if (!is_array($data)) {
            $this->addError(
                $context,
                $pointer,
                sprintf('The \'%s\' parameter should be an array', JsonApiDoc::RELATIONSHIPS)
            );

            return false;
        }

        if (count($data) === 0) {
            $this->addError(
                $context,
                $pointer,
                sprintf('The \'%s\' parameter should not be empty', JsonApiDoc::RELATIONSHIPS)
            );

            return false;
        }

        $isValid = true;
        foreach ($data as $relationName => $relation) {
            $relationPointer = $pointer;
            $relationPointer[] = $relationName;
            if (!$this->validateDataObject($relation, array_merge($relationPointer, [JsonApiDoc::DATA]), $context)) {
                // we have no data in object
                $isValid = false;
                continue;
            }

            $relation = $relation[JsonApiDoc::DATA];
            $relationPointer[] = JsonApiDoc::DATA;
            if ($this->isArrayAssociative($relation)) {
                $isValid = $this->validateResourceObject($relation, $relationPointer, $context) ? $isValid : false;
            } else {
                foreach ($relation as $id => $relationObject) {
                    $this->validateResourceObject(
                        $relationObject,
                        array_merge($relationPointer, [$id]),
                        $context
                    );
                    $isValid = false;
                }
            }
        }

        return $isValid;
    }

    /**
     * Validates attributes block
     *
     * @param array            $data
     * @param array            $pointer
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected function validateAttributes($data, array $pointer, ContextInterface $context)
    {
        if (!is_array($data)) {
            $this->addError(
                $context,
                $pointer,
                sprintf('The \'%s\' parameter should be an array', JsonApiDoc::ATTRIBUTES)
            );

            return false;
        }

        if (count($data) === 0) {
            $this->addError(
                $context,
                $pointer,
                sprintf('The \'%s\' parameter should not be empty', JsonApiDoc::ATTRIBUTES)
            );

            return false;
        }

        if (!$this->isArrayAssociative($data)) {
            $this->addError(
                $context,
                $pointer,
                sprintf('The \'%s\' parameter should be an associative array', JsonApiDoc::ATTRIBUTES)
            );

            return false;
        }

        return true;
    }

    /**
     * Validates JSON API data object. Checks if object have data.
     *
     * @param array            $data
     * @param array            $pointer
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected function validateDataObject($data, array $pointer, ContextInterface $context)
    {
        if (!is_array($data)) {
            $this->addError(
                $context,
                $pointer,
                'Data object have no data'
            );

            return false;
        }

        if (!array_key_exists(JsonApiDoc::DATA, $data)) {
            $this->addError(
                $context,
                $pointer,
                'The primary data object should exist'
            );

            return false;
        }

        if (count($data[JsonApiDoc::DATA]) === 0) {
            $this->addError(
                $context,
                $pointer,
                'The primary data object should not be empty'
            );

            return false;
        }

        return true;
    }

    /**
     * Validates JSON API resource object
     *
     * @param array            $data
     * @param array            $pointer
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected function validateResourceObject(array $data, array $pointer, ContextInterface $context)
    {
        if (!array_key_exists(JsonApiDoc::ID, $data)) {
            $this->addError(
                $context,
                array_merge($pointer, [JsonApiDoc::ID]),
                sprintf('The \'%s\' parameter is required', JsonApiDoc::ID)
            );

            return false;
        }

        if (!array_key_exists(JsonApiDoc::TYPE, $data)) {
            $this->addError(
                $context,
                array_merge($pointer, [JsonApiDoc::TYPE]),
                sprintf('The \'%s\' parameter is required', JsonApiDoc::TYPE)
            );

            return false;
        }

        return true;
    }

    /**
     * Adds validation error to context
     *
     * @param ContextInterface $context
     * @param                  $pointerPathParts
     * @param                  $errorMessage
     */
    protected function addError(ContextInterface $context, $pointerPathParts, $errorMessage)
    {
        $error = new Error();
        $error->setStatusCode(Response::HTTP_BAD_REQUEST);
        $error->setDetail($errorMessage);
        $errorSource = new ErrorSource();
        $errorSource->setPointer($this->getPointer($pointerPathParts));
        $error->setSource($errorSource);

        $context->addError($error);
    }

    /**
     * Returns true if array is associative
     *
     * @param $array
     *
     * @return bool
     */
    protected function isArrayAssociative($array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * @param array $pointerParts
     *
     * @return string
     */
    protected function getPointer(array $pointerParts)
    {
        return sprintf('/%s', implode('/', $pointerParts));
    }
}
