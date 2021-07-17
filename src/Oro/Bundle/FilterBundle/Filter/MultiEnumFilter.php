<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilder;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by a multi-enum entity.
 */
class MultiEnumFilter extends BaseMultiChoiceFilter
{
    const FILTER_TYPE_NAME = 'multi_enum';

    /** @var ManyRelationBuilder */
    protected $manyRelationBuilder;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManyRelationBuilder $manyRelationBuilder
    ) {
        parent::__construct($factory, $util);
        $this->manyRelationBuilder = $manyRelationBuilder;
    }

    /**
     * {@inheritDoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $parameterName = $ds->generateParameterName($this->getName());
        if ($this->isValueRequired($comparisonType)) {
            $ds->setParameter($parameterName, $data['value']);
        }

        return $this->buildComparisonExpr(
            $ds,
            $comparisonType,
            $fieldName,
            $parameterName
        );
    }

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'dictionary';
        if (isset($params['class'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['class'] = $params['class'];
            unset($params['class']);
        }
        if (isset($params['enum_code'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['enum_code'] = $params['enum_code'];
            unset($params['enum_code']);
        }

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $parameterName
    ) {
        return $this->manyRelationBuilder->buildComparisonExpr(
            $ds,
            $fieldName,
            $parameterName,
            $this->getName(),
            in_array($comparisonType, [DictionaryFilterType::NOT_EQUAL, DictionaryFilterType::TYPE_NOT_IN], false)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return EnumFilterType::class;
    }
}
