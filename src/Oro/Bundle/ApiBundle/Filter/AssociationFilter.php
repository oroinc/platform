<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueKeyException;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * The base class for filters that can be used to filter data by different kind of custom associations.
 */
abstract class AssociationFilter extends ComparisonFilter implements
    NamedValueFilterInterface,
    SelfIdentifiableFilterInterface,
    RequestAwareFilterInterface
{
    private RequestType $requestType;
    private ValueNormalizer $valueNormalizer;

    /**
     * {@inheritDoc}
     */
    public function setRequestType(RequestType $requestType): void
    {
        $this->requestType = $requestType;
    }

    public function setValueNormalizer(ValueNormalizer $valueNormalizer): void
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilterValueName(): string
    {
        return 'type';
    }

    protected function getRequestType(): RequestType
    {
        return $this->requestType;
    }

    /**
     * {@inheritDoc}
     */
    public function searchFilterKeys(array $filterValues): array
    {
        $result = [];

        $field = $this->getField();
        $prefix = $field . '.';
        /** @var FilterValue $filterValue */
        foreach ($filterValues as $filterKey => $filterValue) {
            $path = $filterValue->getPath();
            if (str_starts_with($path, $prefix)) {
                $filterValueName = substr($path, \strlen($field) + 1);
                if (empty($filterValueName)) {
                    throw new InvalidFilterValueKeyException(
                        'The target type of an association is not specified.',
                        $filterValue
                    );
                }
                if ($this->getFilterValueName() === $filterValueName) {
                    throw new InvalidFilterValueKeyException(
                        sprintf(
                            'Replace "%s" placeholder with the target type of an association.',
                            $this->getFilterValueName()
                        ),
                        $filterValue
                    );
                }
                $result[] = $filterKey;
            } elseif ($path === $field) {
                throw new InvalidFilterValueKeyException(
                    'The target type of an association is not specified.',
                    $filterValue
                );
            }
        }

        return $result;
    }

    protected function getEntityClass(string $entityType): string
    {
        return $this->valueNormalizer->normalizeValue(
            $entityType,
            DataType::ENTITY_CLASS,
            $this->getRequestType()
        );
    }

    protected function assertFilterValuePath(string $field, string $path): void
    {
        if (!str_starts_with($path, $field . '.')) {
            throw new \InvalidArgumentException(sprintf(
                'The filter value path must starts with "%s".',
                $field
            ));
        }
    }
}
