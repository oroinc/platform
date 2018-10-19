<?php

namespace Oro\Bundle\ApiBundle\Filter;

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
    /** @var RequestType */
    protected $requestType;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * {@inheritdoc}
     */
    public function setRequestType(RequestType $requestType)
    {
        $this->requestType = $requestType;
    }

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function setValueNormalizer(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterValueName()
    {
        return 'type';
    }

    /**
     * {@inheritdoc}
     */
    public function searchFilterKeys(array $filterValues): array
    {
        $result = [];

        $prefix = $this->field . '.';
        /** @var FilterValue $filterValue */
        foreach ($filterValues as $filterKey => $filterValue) {
            $path = $filterValue->getPath();
            if (0 === \strpos($path, $prefix)) {
                $filterValueName = \substr($path, \strlen($this->field) + 1);
                if (empty($filterValueName)) {
                    throw new InvalidFilterValueKeyException(
                        'The target type of an association is not specified.',
                        $filterValue
                    );
                }
                if ($this->getFilterValueName() === $filterValueName) {
                    throw new InvalidFilterValueKeyException(
                        \sprintf(
                            'Replace "%s" placeholder with the target type of an association.',
                            $this->getFilterValueName()
                        ),
                        $filterValue
                    );
                }
                $result[] = $filterKey;
            } elseif ($path === $this->field) {
                throw new InvalidFilterValueKeyException(
                    'The target type of an association is not specified.',
                    $filterValue
                );
            }
        }

        return $result;
    }

    /**
     * @param string $entityType
     *
     * @return string
     */
    protected function getEntityClass($entityType)
    {
        return $this->valueNormalizer->normalizeValue(
            $entityType,
            DataType::ENTITY_CLASS,
            $this->requestType
        );
    }

    /**
     * @param string $field
     * @param string $path
     */
    protected function assertFilterValuePath($field, $path)
    {
        if (0 !== \strpos($path, $field . '.')) {
            throw new \InvalidArgumentException(
                \sprintf('The filter value path must starts with "%s".', $field)
            );
        }
    }
}
