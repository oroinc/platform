<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * @todo this processor should be replaced with a validator in BAP-15805
 */
class ValidateEntityFallback implements ProcessorInterface
{
    /**
     * @var EntityFallbackResolver
     */
    private $fallbackResolver;

    /**
     * @var null|string
     */
    private $entityIncludedType;

    /**
     * @param EntityFallbackResolver $fallbackResolver
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(EntityFallbackResolver $fallbackResolver, ValueNormalizer $valueNormalizer)
    {
        $this->fallbackResolver = $fallbackResolver;
        $this->entityIncludedType = ValueNormalizerUtil::convertToEntityType(
            $valueNormalizer,
            EntityFieldFallbackValue::class,
            new RequestType([RequestType::JSON_API]),
            false
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context|FormContext $context */

        $mainClass = $context->get(Context::CLASS_NAME);
        $requestData = $context->getRequestData();
        if (!array_key_exists(JsonApiDoc::INCLUDED, $requestData)) {
            return;
        }

        $includedData = $this->getCompatibleIncludedRelations($requestData, $this->entityIncludedType, $context);
        if (empty($includedData)) {
            return;
        }

        $relations = $this->getCompatibleRelations($requestData[JsonApiDoc::DATA][JsonApiDoc::RELATIONSHIPS]);

        // match included data with relationships section, and validate entity fallback values provided
        foreach ($includedData as $includedItem) {
            // parse relationships section to get the included item's (fallback compatible) entity field name
            foreach ($relations as $relationName => $relationItem) {
                if ($includedItem[JsonApiDoc::ID] === $relationItem[JsonApiDoc::DATA][JsonApiDoc::ID]) {
                    if (false === $this->isFallbackRequestItemValid($relationName, $includedItem, $mainClass)) {
                        $this->addError(
                            $this->buildPointer([JsonApiDoc::INCLUDED, $this->entityIncludedType]),
                            $this->getInvalidIncludedFallbackItemMessage($includedItem[JsonApiDoc::ID]),
                            $context
                        );
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param array $relations
     * @return array
     */
    private function getCompatibleRelations(array $relations)
    {
        return array_filter(
            $relations,
            function ($relation) {
                return (isset($relation[JsonApiDoc::DATA][JsonApiDoc::TYPE])
                    && $relation[JsonApiDoc::DATA][JsonApiDoc::TYPE] === $this->entityIncludedType);
            }
        );
    }

    /**
     * @param string $relationName
     * @param array $fallbackRequestData
     * @param string $mainEntityClass
     * @return bool
     */
    private function isFallbackRequestItemValid($relationName, $fallbackRequestData, $mainEntityClass)
    {
        $fallbackFiltered = array_filter(
            $fallbackRequestData[JsonApiDoc::ATTRIBUTES],
            function ($value) {
                return !is_null($value);
            }
        );

        // only one supplied value is valid
        if (1 !== count($fallbackFiltered)) {
            return false;
        }

        $fallbackConfig = $this->fallbackResolver->getFallbackConfig(
            new $mainEntityClass(),
            $relationName,
            EntityFieldFallbackValue::FALLBACK_LIST
        );
        $attributes = $fallbackRequestData[JsonApiDoc::ATTRIBUTES];

        // check if correct fallback type provided
        if (isset($attributes[EntityFieldFallbackValue::FALLBACK_PARENT_FIELD])) {
            return in_array(
                $attributes[EntityFieldFallbackValue::FALLBACK_PARENT_FIELD],
                array_keys($fallbackConfig)
            );
        }

        // Get required fallback value type
        $valueType = $this->fallbackResolver->getType(new $mainEntityClass(), $relationName);
        // Choose which field of a fallback definition is required
        $requiredValueField = $this->fallbackResolver->getRequiredFallbackFieldByType($valueType);
        //we have special cases when the required field is scalar value but the value type is array
        //check pageTemplate, we have data transformer that converts the scalar value to array value
        if (in_array($relationName, EntityFieldFallbackValue::$specialRelations)) {
            $requiredValueField = EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD;
        }

        // Check if valid scalar data provided
        if ($requiredValueField === EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD) {
            return (
                isset($attributes[EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD])
                && is_scalar($attributes[EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD])
            );
        }

        // Check if valid array data provided
        if ($requiredValueField === EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD) {
            return (
                isset($attributes[EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD])
                && is_array($attributes[EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD])
            );
        }

        return false;
    }

    /**
     * @param array $requestData
     * @param string $entityIncludedType
     * @param Context $context
     * @return array
     */
    private function getCompatibleIncludedRelations(array $requestData, $entityIncludedType, Context $context)
    {
        $result = [];

        foreach ($requestData[JsonApiDoc::INCLUDED] as $includedItemIndex => $includedItem) {
            if (!isset($includedItem[JsonApiDoc::TYPE])
                || $entityIncludedType !== $includedItem[JsonApiDoc::TYPE]
            ) {
                continue;
            }

            if (!isset($includedItem[JsonApiDoc::ATTRIBUTES])) {
                $this->addError(
                    $this->buildPointer([JsonApiDoc::INCLUDED, $this->entityIncludedType]),
                    $this->getInvalidIncludedFallbackItemMessage($includedItemIndex),
                    $context
                );
                $result = [];
                break;
            }

            $result[$includedItem[JsonApiDoc::ID]] = $includedItem;
        }

        return $result;
    }

    /**
     * @param string $itemId
     *
     * @return string
     */
    private function getInvalidIncludedFallbackItemMessage($itemId)
    {
        return sprintf(
            "Invalid entity fallback value provided for the included value with id '%s'." .
            " Please provide a correct id, and an attribute section with either a '%s' identifier, an '%s' or '%s'",
            $itemId,
            EntityFieldFallbackValue::FALLBACK_PARENT_FIELD,
            EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD,
            EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD
        );
    }

    /**
     * @param string $pointer
     * @param string $message
     * @param Context $context
     */
    private function addError($pointer, $message, Context $context)
    {
        $error = Error::createValidationError(Constraint::REQUEST_DATA, $message)
            ->setSource(ErrorSource::createByPointer($pointer));

        $context->addError($error);
    }

    /**
     * @param array $properties
     * @param string|null $parentPointer
     * @return string
     *
     */
    private function buildPointer(array $properties, $parentPointer = null)
    {
        array_unshift($properties, $parentPointer);

        return implode('/', $properties);
    }
}
