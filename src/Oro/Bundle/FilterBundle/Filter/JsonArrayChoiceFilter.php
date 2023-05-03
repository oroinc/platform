<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Comparison;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;

/**
 * The filter by predefined values that allow to select items from a choice list.
 * This filter is intended to be used for JSON fields that store an array.
 */
class JsonArrayChoiceFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $formView = $this->getFormView();
        $fieldView = $formView->children['value'];

        $metadata = parent::getMetadata();
        $metadata['choices'] = array_map(
            function (ChoiceView $choice) {
                return ['label' => $choice->label, 'value' => $choice->value];
            },
            $fieldView->vars['choices']
        );
        $metadata['populateDefault'] = $formView->vars['populate_default'];
        $metadata[FilterUtility::TYPE_KEY] = $fieldView->vars['multiple'] ? 'multiselect' : 'select';

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $isPostgres = $ds->getDatabasePlatform() instanceof PostgreSQL94Platform;
        $expr = [];
        foreach ($data['value'] as $val) {
            $expr[] = $isPostgres
                ? $this->getExpressionForPostgreSql($ds, $fieldName, $val)
                : $this->getExpression($ds, $fieldName, $val);
        }

        $count = count($expr);
        if (0 === $count) {
            return null;
        }
        if (1 === $count) {
            return $expr[0];
        }

        return $ds->expr()->orX(...$expr);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return ChoiceFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function parseData($data)
    {
        $data = parent::parseData($data);
        if (!\is_array($data) || !isset($data['value'])) {
            return false;
        }

        $value = $data['value'];
        if ('' === $value || ((\is_array($value) || $value instanceof Collection) && count($value) === 0)) {
            return false;
        }

        if ($value instanceof Collection) {
            $value = $value->getValues();
        }
        if (!\is_array($value)) {
            $value = [$value];
        }
        $data['value'] = $value;

        return $data;
    }

    private function getExpression(
        FilterDatasourceAdapterInterface $ds,
        string $fieldName,
        mixed $value
    ): Comparison {
        $parameterName = $ds->generateParameterName($this->getName());
        $ds->setParameter($parameterName, '%"' . $value . '"%');

        return $ds->expr()->like($fieldName, $parameterName, true);
    }

    private function getExpressionForPostgreSql(
        FilterDatasourceAdapterInterface $ds,
        string $fieldName,
        mixed $value
    ): Comparison {
        $parameterName = ':' . $ds->generateParameterName($this->getName());
        $parameterArrayContains = ':' . $ds->generateParameterName($this->getName());
        $ds->setParameter($parameterName, json_encode($value, JSON_THROW_ON_ERROR));
        $ds->setParameter($parameterArrayContains, true, Types::BOOLEAN);

        return $ds->expr()->eq(
            sprintf('ARRAY_CONTAINS(%s, %s)', $fieldName, $parameterName),
            $parameterArrayContains
        );
    }
}
