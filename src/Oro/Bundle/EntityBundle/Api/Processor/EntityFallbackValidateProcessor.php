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
     * @var ValueNormalizer
     */
    protected $valueNormalizer;

    /**
     * @param EntityFallbackResolver $fallbackResolver
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(EntityFallbackResolver $fallbackResolver, ValueNormalizer $valueNormalizer)
    {
        $this->fallbackResolver = $fallbackResolver;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $mainClass = $context->get(Context::CLASS_NAME);
        $requestData = $context->getRequestData();
        if (!array_key_exists(JsonApiDoc::INCLUDED, $requestData)
            || !is_array($requestData[JsonApiDoc::INCLUDED])
            || !isset($requestData[JsonApiDoc::DATA][JsonApiDoc::RELATIONSHIPS])
            || !is_array($requestData[JsonApiDoc::DATA][JsonApiDoc::RELATIONSHIPS])
            || !isset($mainClass)
            || !class_exists($mainClass)
        ) {
            return;
        }
        $relations = $requestData[JsonApiDoc::DATA][JsonApiDoc::RELATIONSHIPS];
        $entityIncludedType = ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            EntityFieldFallbackValue::class,
            new RequestType([RequestType::JSON_API]),
            false
        );

        try {
            $includedData = $this->getCompatibleIncludedRelations($requestData, $entityIncludedType);
        } catch (InvalidIncludedFallbackItemException $e) {
            // convert validation exceptions into a context type error
            $this->addError($this->buildPointer([JsonApiDoc::INCLUDED, $entityIncludedType]), $e->getMessage(), $context);

            return;
        }

        // match included data with relationships section, and validate entity fallback values provided
        foreach ($includedData as $includedItem) {
            // parse relationships section to get the included item's (fallback compatible) entity field name
            foreach ($relations as $relationName => $relationItem) {
                if (isset($relationItem[JsonApiDoc::DATA])
                    && isset($relationItem[JsonApiDoc::DATA][JsonApiDoc::ID])
                    && $includedItem[JsonApiDoc::ID] === $relationItem[JsonApiDoc::DATA][JsonApiDoc::ID]
                ) {
                    if (false === $this->isFallbackRequestItemValid($relationName, $includedItem, $mainClass)) {
                        $this->addError(
                            $this->buildPointer([JsonApiDoc::INCLUDED, $entityIncludedType]),
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
     * @param string $relationName
     * @param array $fallbackRequestData
     * @param string $mainEntityClass
     * @return bool
     */
    protected function isFallbackRequestItemValid($relationName, $fallbackRequestData, $mainEntityClass)
    {
        $fallbackFiltered = array_filter($fallbackRequestData[JsonApiDoc::ATTRIBUTES], function($value) {
            return !is_null($value);
        });

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

            if (!$this->isIncludedItemValid($includedItem)) {
                throw new InvalidIncludedFallbackItemException($includedItemIndex);
            }

            $result[$includedItem[JsonApiDoc::ID]] = $includedItem;
        }

        return $result;
    }

    /**
     * @param array $includedItem
     * @return bool
     */
    protected function isIncludedItemValid(array $includedItem)
    {
        return (isset($includedItem[JsonApiDoc::ID])
            && isset($includedItem[JsonApiDoc::ATTRIBUTES])
            && is_array($includedItem[JsonApiDoc::ATTRIBUTES])
        );
    }
}
