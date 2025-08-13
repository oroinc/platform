<?php

namespace Oro\Bundle\SearchBundle\Api;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * A helper class to parse a value of a filter for the list of entities to search for.
 */
class SearchEntityListFilterHelper
{
    private SearchEntityClassProviderInterface $searchEntityClassProvider;
    private ValueNormalizer $valueNormalizer;

    public function __construct(
        SearchEntityClassProviderInterface $searchEntityClassProvider,
        ValueNormalizer $valueNormalizer
    ) {
        $this->searchEntityClassProvider = $searchEntityClassProvider;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * Gets the list of search aliases for an entities provided by the given filter.
     * When the filter value has some invalid data, the appropriate errors will be added to the context.
     *
     * @param Context $context
     * @param string  $filterName
     *
     * @return string[] [entity class => entity search alias, ...]
     */
    public function getEntities(Context $context, string $filterName): array
    {
        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $allowedEntityClasses = $this->searchEntityClassProvider->getAllowedEntityClasses($version, $requestType);
        $filterValue = $context->getFilterValues()->getOne($filterName);
        if (null === $filterValue) {
            return $allowedEntityClasses;
        }

        $entityTypes = (array)$filterValue->getValue();
        if (!$entityTypes) {
            return [];
        }

        $result = [];
        $accessibleEntityClasses = $this->searchEntityClassProvider->getAccessibleEntityClasses($version, $requestType);
        foreach ($entityTypes as $entityType) {
            $entityClass = ValueNormalizerUtil::tryConvertToEntityClass(
                $this->valueNormalizer,
                $entityType,
                $requestType
            );
            if ($entityClass) {
                $searchAlias = $accessibleEntityClasses[$entityClass] ?? null;
                if (!$searchAlias) {
                    $context->addError(
                        $this->createInvalidFilterValueKeyError(
                            $filterValue->getSourceKey(),
                            $entityType,
                            $accessibleEntityClasses,
                            $requestType
                        )
                    );
                } elseif (isset($allowedEntityClasses[$entityClass])) {
                    $result[$entityClass] = $searchAlias;
                }
            } else {
                $context->addError(
                    $this->createInvalidFilterValueKeyError(
                        $filterValue->getSourceKey(),
                        $entityType,
                        $accessibleEntityClasses,
                        $requestType
                    )
                );
            }
        }

        return $result;
    }

    private function createInvalidFilterValueKeyError(
        string $filterKey,
        string $entityType,
        array $accessibleEntityClasses,
        RequestType $requestType
    ): Error {
        return Error::createValidationError(
            Constraint::FILTER,
            \sprintf(
                'The "%s" is not known entity. Known entities: %s',
                $entityType,
                implode(', ', $this->convertToEntityTypes($accessibleEntityClasses, $requestType))
            )
        )->setSource(ErrorSource::createByParameter($filterKey));
    }

    private function convertToEntityTypes(array $entityClasses, RequestType $requestType): array
    {
        $entityTypes = [];
        foreach ($entityClasses as $entityClass => $searchAlias) {
            $entityType = ValueNormalizerUtil::tryConvertToEntityType(
                $this->valueNormalizer,
                $entityClass,
                $requestType
            );
            if ($entityType) {
                $entityTypes[] = $entityType;
            }
        }

        return $entityTypes;
    }
}
