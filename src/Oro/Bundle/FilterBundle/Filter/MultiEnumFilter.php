<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilder;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;

class MultiEnumFilter extends BaseMultiChoiceFilter
{
    /** @var ManyRelationBuilder */
    protected $manyRelationBuilder;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     * @param ManyRelationBuilder  $manyRelationBuilder
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManyRelationBuilder $manyRelationBuilder
    ) {
        parent::__construct($factory, $util);
        $this->manyRelationBuilder = $manyRelationBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $type =  $data['type'];
        $parameterName = $ds->generateParameterName($this->getName());

        $this->applyFilterToClause(
            $ds,
            $this->buildComparisonExpr(
                $ds,
                $type,
                $this->get(FilterUtility::DATA_NAME_KEY),
                $parameterName
            )
        );

        if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY], true)) {
            $ds->setParameter($parameterName, $data['value']);
        }

        return true;
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
            in_array($comparisonType, [DictionaryFilterType::NOT_EQUAL, DictionaryFilterType::TYPE_NOT_IN], true)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return EnumFilterType::NAME;
    }
}
