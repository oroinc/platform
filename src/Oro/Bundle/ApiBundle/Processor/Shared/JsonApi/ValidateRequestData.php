<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * A base processor to validate that the request data contains valid JSON.API object.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class ValidateRequestData implements ProcessorInterface
{
    private const ROOT_POINTER = '';

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
            $requestData = $context->getRequestData();
            if ($context->hasIdentifierFields()) {
                if ($this->validateRequestData($requestData, JsonApiDoc::DATA)) {
                    $data = $requestData[JsonApiDoc::DATA];
                    $dataPointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::DATA);
                    $this->validatePrimaryDataObject($data, $dataPointer);
                    $this->validateAttributesAndRelationships($data, $dataPointer);
                    $this->validateIncludedEntities($requestData);
                }
            } elseif ($this->validateRequestData($requestData, JsonApiDoc::META)) {
                $this->validateSectionNotExist($requestData, JsonApiDoc::DATA);
                $this->validateSectionNotExist($requestData, JsonApiDoc::INCLUDED);
            }
        } finally {
            $this->context = null;
        }
    }

    /**
     * @param array  $data
     * @param string $rootSection
     *
     * @return bool
     */
    protected function validateRequestData(array $data, $rootSection)
    {
        $isValid = true;
        if (!array_key_exists($rootSection, $data)) {
            $this->addError(
                $this->buildPointer(self::ROOT_POINTER, $rootSection),
                sprintf('The primary %s object should exist', strtolower($rootSection))
            );
            $isValid = false;
        } elseif (empty($data[$rootSection])) {
            $this->addError(
                $this->buildPointer(self::ROOT_POINTER, $rootSection),
                sprintf('The primary %s object should not be empty', strtolower($rootSection))
            );
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param array  $data
     * @param string $section
     */
    protected function validateSectionNotExist(array $data, $section)
    {
        if (array_key_exists($section, $data)) {
            $this->addError(
                $this->buildPointer(self::ROOT_POINTER, $section),
                sprintf('The \'%s\' section should not exist', $section)
            );
        }
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
        $isValid = true;
        $dataClassName = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $data[JsonApiDoc::TYPE],
            $this->context->getRequestType(),
            false
        );
        if ($dataClassName !== $this->context->getClassName()) {
            $this->addConflictError(
                $this->buildPointer($pointer, JsonApiDoc::TYPE),
                sprintf(
                    'The \'%s\' property of the primary data object should match the requested resource',
                    JsonApiDoc::TYPE
                )
            );
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param array  $data
     * @param string $pointer
     */
    protected function validateAttributesAndRelationships(array $data, $pointer)
    {
        if (array_key_exists(JsonApiDoc::ATTRIBUTES, $data)) {
            $this->validateArray($data, JsonApiDoc::ATTRIBUTES, $pointer, true, true);
        }
        if (array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)
            && $this->validateArray($data, JsonApiDoc::RELATIONSHIPS, $pointer, true, true)
        ) {
            $this->validateRelationships(
                $data[JsonApiDoc::RELATIONSHIPS],
                $this->buildPointer($pointer, JsonApiDoc::RELATIONSHIPS)
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
            if ($relationData) {
                if (!ArrayUtil::isAssoc($relationData)) {
                    foreach ($relationData as $key => $value) {
                        if ($this->validateResourceObject($value, $this->buildPointer($relationDataPointer, $key))) {
                            $isValid = false;
                        }
                    }
                } elseif (!$this->validateResourceObject($relationData, $relationDataPointer)) {
                    $isValid = false;
                }
            }
        }

        return $isValid;
    }

    /**
     * @param array $data
     */
    protected function validateIncludedEntities(array $data)
    {
        if (array_key_exists(JsonApiDoc::INCLUDED, $data)
            && $this->validateArray($data, JsonApiDoc::INCLUDED, self::ROOT_POINTER, true)
        ) {
            $includedPointer = $this->buildPointer(self::ROOT_POINTER, JsonApiDoc::INCLUDED);
            foreach ($data[JsonApiDoc::INCLUDED] as $key => $item) {
                $this->validateResourceObject($item, $this->buildPointer($includedPointer, $key));
            }
        }
    }

    /**
     * @param array  $data
     * @param string $pointer
     *
     * @return bool
     */
    protected function validateResourceObject(array $data, $pointer)
    {
        $isValid = true;
        if (!$this->validateRequiredNotBlankString($data, JsonApiDoc::TYPE, $pointer)) {
            $isValid = false;
        }
        if (!$this->validateRequiredNotBlankString($data, JsonApiDoc::ID, $pointer)) {
            $isValid = false;
        }

        return $isValid;
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
        $isValid = true;
        if (!array_key_exists($property, $data)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property is required', $property)
            );
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param array  $data
     * @param string $property
     * @param string $pointer
     *
     * @return bool
     */
    protected function validateRequiredNotBlankString(array $data, $property, $pointer)
    {
        return
            $this->validateRequired($data, $property, $pointer)
            && $this->validateNotBlankString($data, $property, $pointer);
    }

    /**
     * @param array  $data
     * @param string $property
     * @param string $pointer
     *
     * @return bool
     */
    protected function validateNotBlankString(array $data, $property, $pointer)
    {
        $isValid = true;
        if (array_key_exists($property, $data)) {
            $value = $data[$property];
            if (null === $value) {
                $this->addError(
                    $this->buildPointer($pointer, $property),
                    sprintf('The \'%s\' property should not be null', $property)
                );
                $isValid = false;
            } elseif (!is_string($value)) {
                $this->addError(
                    $this->buildPointer($pointer, $property),
                    sprintf('The \'%s\' property should be a string', $property)
                );
                $isValid = false;
            } elseif ('' === trim($value)) {
                $this->addError(
                    $this->buildPointer($pointer, $property),
                    sprintf('The \'%s\' property should not be blank', $property)
                );
                $isValid = false;
            }
        }

        return $isValid;
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
        $isValid = true;
        $value = $data[$property];
        if (!is_array($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should be an array', $property)
            );
            $isValid = false;
        } elseif ($notEmpty && empty($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should not be empty', $property)
            );
            $isValid = false;
        } elseif ($associative && !ArrayUtil::isAssoc($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should be an associative array', $property)
            );
            $isValid = false;
        }

        return $isValid;
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

    /**
     * @param string $pointer
     * @param string $message
     */
    protected function addConflictError($pointer, $message)
    {
        $error = Error::createValidationError(Constraint::CONFLICT, $message, Response::HTTP_CONFLICT)
            ->setSource(ErrorSource::createByPointer($pointer));

        $this->context->addError($error);
    }
}
