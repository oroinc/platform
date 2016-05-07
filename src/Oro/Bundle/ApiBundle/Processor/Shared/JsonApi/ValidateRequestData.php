<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

abstract class ValidateRequestData implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var SingleItemContext */
    protected $context;

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
        /** @var FormContext|SingleItemContext $context */

        $this->context = $context;
        try {
            $pointer = $this->buildPointer('', JsonApiDoc::DATA);
            $requestData = $context->getRequestData();
            if ($this->validateRequestData($requestData, $pointer)) {
                $data = $requestData[JsonApiDoc::DATA];
                $this->validatePrimaryDataObject($data, $pointer);
                $this->validateAttributesAndRelationships($data, $pointer);
            }
            $this->context = null;
        } catch (\Exception $e) {
            $this->context = null;
            throw $e;
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
                'The primary data object should exist'
            );

            return false;
        }
        if (empty($data[JsonApiDoc::DATA])) {
            $this->addError(
                $pointer,
                'The primary data object should not be empty'
            );

            return false;
        }

        return true;
    }

    /**
     * @param array  $data
     * @param string $pointer
     */
    abstract protected function validatePrimaryDataObject(array $data, $pointer);

    /**
     * @param array  $data
     * @param string $pointer
     *
     * @return bool
     */
    protected function validatePrimaryDataObjectType(array $data, $pointer)
    {
        $dataClassName = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $data[JsonApiDoc::TYPE],
            $this->context->getRequestType(),
            false
        );
        if ($dataClassName !== $this->context->getClassName()) {
            $this->addError(
                $this->buildPointer($pointer, JsonApiDoc::TYPE),
                sprintf(
                    'The \'%s\' property of the primary data object should match the requested resource',
                    JsonApiDoc::TYPE
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @param array  $data
     * @param string $pointer
     */
    protected function validateAttributesAndRelationships(array $data, $pointer)
    {
        $hasAttributes = false;
        $hasRelationships = false;
        if (array_key_exists(JsonApiDoc::ATTRIBUTES, $data)) {
            $hasAttributes = true;
            $this->validateArray($data, JsonApiDoc::ATTRIBUTES, $pointer, true, true);
        }
        if (array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)) {
            $hasRelationships = true;
            if ($this->validateArray($data, JsonApiDoc::RELATIONSHIPS, $pointer, true, true)) {
                $this->validateRelationships(
                    $data[JsonApiDoc::RELATIONSHIPS],
                    $this->buildPointer($pointer, JsonApiDoc::RELATIONSHIPS)
                );
            }
        }
        if (!$hasAttributes && !$hasRelationships) {
            $this->addError(
                $pointer,
                sprintf(
                    'The primary data object should contain \'%s\' or \'%s\' block',
                    JsonApiDoc::ATTRIBUTES,
                    JsonApiDoc::RELATIONSHIPS
                )
            );
        }
    }

    /**
     * @param array  $data
     * @param string $pointer
     *
     * @return bool
     */
    protected function validateRelationships(array $data, $pointer)
    {
        $isValid = true;
        foreach ($data as $relationName => $relation) {
            $relationPointer = $this->buildPointer($pointer, $relationName);
            if (!is_array($relation) || !array_key_exists(JsonApiDoc::DATA, $relation)) {
                $this->addError(
                    $relationPointer,
                    sprintf('The relationship should have \'%s\' property', JsonApiDoc::DATA)
                );
                $isValid = false;
                continue;
            }
            if (null === $relation[JsonApiDoc::DATA]) {
                continue;
            }
            if (!$this->validateArray($relation, JsonApiDoc::DATA, $relationPointer)) {
                $isValid = false;
                continue;
            }

            $relationData = $relation[JsonApiDoc::DATA];
            $relationDataPointer = $this->buildPointer($relationPointer, JsonApiDoc::DATA);
            if (ArrayUtil::isAssoc($relationData)) {
                if (!$this->validateResourceObject($relationData, $relationDataPointer)) {
                    $isValid = false;
                }
            } else {
                foreach ($relationData as $key => $value) {
                    if ($this->validateResourceObject($value, $this->buildPointer($relationDataPointer, $key))) {
                        $isValid = false;
                    }
                }
            }
        }

        return $isValid;
    }

    /**
     * @param array  $data
     * @param string $pointer
     *
     * @return bool
     */
    protected function validateResourceObject(array $data, $pointer)
    {
        if (!$this->validateRequired($data, JsonApiDoc::ID, $pointer)) {
            return false;
        }
        if (!$this->validateRequired($data, JsonApiDoc::TYPE, $pointer)) {
            return false;
        }

        return true;
    }

    /**
     * @param array  $data
     * @param string $property
     * @param string $pointer
     *
     * @return bool
     */
    protected function validateRequired(array $data, $property, $pointer)
    {
        if (!array_key_exists($property, $data)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property is required', $property)
            );

            return false;
        }

        return true;
    }

    /**
     * @param mixed  $data
     * @param string $property
     * @param string $pointer
     * @param bool   $notEmpty
     * @param bool   $associative
     *
     * @return bool
     */
    protected function validateArray($data, $property, $pointer, $notEmpty = false, $associative = false)
    {
        $value = $data[$property];

        if (!is_array($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should be an array', $property)
            );

            return false;
        }
        if ($notEmpty && empty($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should not be empty', $property)
            );

            return false;
        }
        if ($associative && !ArrayUtil::isAssoc($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should be an associative array', $property)
            );

            return false;
        }

        return true;
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
