<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;

/**
 * The filter by predefined values.
 */
class ChoiceFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return ChoiceFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        if (isset($params['null_value'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['null_value'] = $params['null_value'];
        }
        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        if ($this->checkNullValue($data)) {
            if (empty($data['value'])) {
                return $this->buildNullValueExpr($ds, $comparisonType, $fieldName);
            }

            $parameterName = $ds->generateParameterName($this->getName());
            $ds->setParameter($parameterName, $data['value']);

            return $this->buildCombinedExpr(
                $ds,
                $comparisonType,
                $this->buildComparisonExpr($ds, $comparisonType, $fieldName, $parameterName),
                $this->buildNullValueExpr($ds, $comparisonType, $fieldName)
            );
        }

        $parameterName = $ds->generateParameterName($this->getName());
        $ds->setParameter($parameterName, $data['value']);

        return $this->buildComparisonExpr($ds, $comparisonType, $fieldName, $parameterName);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $formView = $this->getFormView();
        $fieldView = $formView->children['value'];

        $choices = array_map(
            function (ChoiceView $choice) {
                return [
                    'label' => $choice->label,
                    'value' => $choice->value
                ];
            },
            $fieldView->vars['choices']
        );

        $metadata = parent::getMetadata();
        $metadata['choices'] = $choices;
        $metadata['populateDefault'] = $formView->vars['populate_default'];
        if (!empty($formView->vars['default_value'])) {
            $metadata['placeholder'] = $formView->vars['default_value'];
        }
        if (!empty($formView->vars['null_value'])) {
            $metadata['nullValue'] = $formView->vars['null_value'];
        }
        if ($fieldView->vars['multiple']) {
            $metadata[FilterUtility::TYPE_KEY] = 'multichoice';
        }

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        if (isset($data['value']) && !$this->getOr('keep_string_value', false)) {
            if (\is_array($data['value'])) {
                foreach ($data['value'] as $key => $value) {
                    if (is_numeric($value)) {
                        $data['value'][$key] = (int)$value;
                    }
                }
            } elseif (is_numeric($data['value'])) {
                $data['value'] = (int)$data['value'];
            }
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function parseData($data)
    {
        $data = parent::parseData($data);
        if (!\is_array($data)) {
            return false;
        }

        return $this->parseValue($data);
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    protected function parseValue(array $data)
    {
        if (!isset($data['value'])) {
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

    /**
     * Build an expression used to filter data
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int                              $comparisonType
     * @param string                           $fieldName
     * @param string                           $parameterName
     *
     * @return mixed
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $parameterName
    ) {
        switch ($comparisonType) {
            case ChoiceFilterType::TYPE_NOT_CONTAINS:
                return $ds->expr()->notIn($fieldName, $parameterName, true);
            default:
                return $ds->expr()->in($fieldName, $parameterName, true);
        }
    }

    /**
     * Build an expression used to filter data by null value
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int                              $comparisonType
     * @param string                           $fieldName
     *
     * @return mixed
     */
    protected function buildNullValueExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName
    ) {
        switch ($comparisonType) {
            case ChoiceFilterType::TYPE_NOT_CONTAINS:
                return $ds->expr()->isNotNull($fieldName);
            default:
                return $ds->expr()->isNull($fieldName);
        }
    }

    /**
     * Build an expression contains both comparison and null value expressions
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int                              $comparisonType
     * @param mixed                            $comparisonExpr
     * @param mixed                            $nullValueExpr
     *
     * @return mixed
     */
    protected function buildCombinedExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $comparisonExpr,
        $nullValueExpr
    ) {
        switch ($comparisonType) {
            case ChoiceFilterType::TYPE_NOT_CONTAINS:
                return $ds->expr()->andX($comparisonExpr, $nullValueExpr);
            default:
                return $ds->expr()->orX($comparisonExpr, $nullValueExpr);
        }
    }

    /**
     * Check if null value option is selected and if so remove it from $data array
     *
     * @param array $data
     *
     * @return bool TRUE if null value is selected; otherwise, FALSE
     */
    protected function checkNullValue(array &$data)
    {
        $nullValue = $this->getOr('null_value');
        $isNullValueSelected = false;
        if ($nullValue) {
            $values = $data['value'];
            foreach ($values as $key => $value) {
                if ($value === $nullValue) {
                    unset($values[$key]);
                    $data['value'] = $values;
                    $isNullValueSelected = true;
                    break;
                }
            }
        }

        return $isNullValueSelected;
    }
}
