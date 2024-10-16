<?php

namespace Oro\Bundle\ApiBundle\Datagrid;

use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;

/**
 * The filter by OpenAPI specification status.
 */
class OpenApiSpecificationStatusFilter extends ChoiceFilter
{
    private const STATUS_PUBLISHED = 'published';
    private const VALUE = 'value';

    #[\Override]
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        if (!\is_array($data) || empty($data[self::VALUE])) {
            return parent::buildExpr($ds, $comparisonType, $fieldName, $data);
        }

        if (\in_array(self::STATUS_PUBLISHED, $data[self::VALUE], true)) {
            $expr = $this->getPublishedExpr($ds, $fieldName);
            $this->removeSelectedStatus($data, self::STATUS_PUBLISHED);
            if ($data[self::VALUE]) {
                $parentExpr = parent::buildExpr($ds, $comparisonType, $fieldName, $data);
                $expr = $ds->expr()->orX($expr, $parentExpr);
            }

            return $expr;
        }

        $parentExpr = parent::buildExpr($ds, $comparisonType, $fieldName, $data);
        if (\in_array(OpenApiSpecification::STATUS_RENEWING, $data[self::VALUE], true)) {
            if (\count($data[self::VALUE]) > 1) {
                $this->removeSelectedStatus($data, OpenApiSpecification::STATUS_RENEWING);

                return $ds->expr()->orX(
                    $this->getRenewingExpr($ds, $fieldName),
                    $ds->expr()->andX($this->getNotPublishedExpr($ds, $fieldName), $parentExpr)
                );
            }

            return $parentExpr;
        }

        return $ds->expr()->andX($this->getNotPublishedExpr($ds, $fieldName), $parentExpr);
    }

    private function getPublishedExpr(FilterDatasourceAdapterInterface $ds, string $fieldName): string
    {
        $parameterName = $ds->generateParameterName($this->getName());
        $ds->setParameter($parameterName, true);

        return $ds->expr()->eq($this->getPublishedFieldName($fieldName), $parameterName, true);
    }

    private function getNotPublishedExpr(FilterDatasourceAdapterInterface $ds, string $fieldName): string
    {
        $parameterName = $ds->generateParameterName($this->getName());
        $ds->setParameter($parameterName, true);

        return $ds->expr()->neq($this->getPublishedFieldName($fieldName), $parameterName, true);
    }

    private function getRenewingExpr(FilterDatasourceAdapterInterface $ds, string $fieldName): string
    {
        $parameterName = $ds->generateParameterName($this->getName());
        $ds->setParameter($parameterName, OpenApiSpecification::STATUS_RENEWING);

        return $this->buildComparisonExpr($ds, ChoiceFilterType::TYPE_CONTAINS, $fieldName, $parameterName);
    }

    private function getPublishedFieldName(string $fieldName): string
    {
        return substr($fieldName, 0, strrpos($fieldName, '.') + 1) . 'published';
    }

    private function removeSelectedStatus(array &$data, string $status): void
    {
        unset($data[self::VALUE][array_search($status, $data[self::VALUE], true)]);
    }
}
