<?php

namespace Oro\Bundle\EntityExtendBundle\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\EntityExtendBundle\Form\Type\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\ManyRelationBuilder;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;

class DictionaryFilter extends AbstractMultiChoiceFilter
{
    /**
     * Constructor
     *
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
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'choice';
        if (isset($params['class'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['class'] = $params['class'];
            unset($params['class']);
        }
        if (isset($params['dictionary_code'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['dictionary_code'] = $params['dictionary_code'];
            unset($params['dictionary_code']);
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
            $comparisonType === ChoiceFilterType::TYPE_NOT_CONTAINS
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function buildNullValueExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName
    ) {
        return $this->manyRelationBuilder->buildNullValueExpr(
            $ds,
            $fieldName,
            $this->getName(),
            $comparisonType === ChoiceFilterType::TYPE_NOT_CONTAINS
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return DictionaryFilterType::NAME;
    }
}
