<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ContextErrorUtilTrait;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\Api\InvalidIncludedFallbackItemException;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class EntityFallbackValidateProcessor implements ProcessorInterface
{
    use ContextErrorUtilTrait;

    /**
     * @var EntityFallbackResolver
     */
    protected $fallbackResolver;

    /**
     * @var null|string
     */
    protected $entityIncludedType;

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
        $mainClass = $context->get(Context::CLASS_NAME);
        $requestData = $context->getRequestData();
        if (!array_key_exists(JsonApiDoc::INCLUDED, $requestData)) {
            return;
        }

        try {
            $includedData = $this->getCompatibleIncludedRelations($requestData, $this->entityIncludedType);
        } catch (InvalidIncludedFallbackItemException $e) {
            // convert validation exceptions into a context type error
            $this->addError(
                $this->buildPointer([JsonApiDoc::INCLUDED, $this->entityIncludedType]),
                $e->getMessage(),
                $context
            );

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
                            (new InvalidIncludedFallbackItemException($includedItem[JsonApiDoc::ID]))->getMessage(),
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
    protected function getCompatibleRelations(array $relations)
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
    protected function isFallbackRequestItemValid($relationName, $fallbackRequestData, $mainEntityClass)
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
     * @return array
     * @throws InvalidIncludedFallbackItemException
     */
    protected function getCompatibleIncludedRelations(array $requestData, $entityIncludedType)
    {
        $result = [];

        foreach ($requestData[JsonApiDoc::INCLUDED] as $includedItemIndex => $includedItem) {
            if (!isset($includedItem[JsonApiDoc::TYPE])
                || $entityIncludedType !== $includedItem[JsonApiDoc::TYPE]
            ) {
                continue;
            }

            if (!isset($includedItem[JsonApiDoc::ATTRIBUTES])) {
                throw new InvalidIncludedFallbackItemException($includedItemIndex);
            }

            $result[$includedItem[JsonApiDoc::ID]] = $includedItem;
        }

        return $result;
    }
}
