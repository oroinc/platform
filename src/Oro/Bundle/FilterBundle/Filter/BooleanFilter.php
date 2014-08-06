<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class BooleanFilter extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return BooleanFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        // static option for metadata
        $params['contextSearch'] = false;
        parent::init($name, $params);
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

        $this->applyFilterToClause(
            $ds,
            $this->buildComparisonExpr(
                $ds,
                $data['value'],
                $this->get(FilterUtility::DATA_NAME_KEY)
            )
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $formView  = $this->getForm()->createView();
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


        $metadata            = parent::getMetadata();
        $metadata['choices'] = $choices;

        return $metadata;
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    public function parseData($data)
    {
        $allowedValues = array(BooleanFilterType::TYPE_YES, BooleanFilterType::TYPE_NO);
        if (!is_array($data)
            || !array_key_exists('value', $data)
            || !$data['value']
            || !in_array($data['value'], $allowedValues)
        ) {
            return false;
        }

        return $data;
    }

    /**
     * Build an expression used to filter data
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int                              $comparisonType 0 to compare with false, 1 to compare with true
     * @param string                           $fieldName
     * @return string
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName
    ) {
        switch ($comparisonType) {
            case BooleanFilterType::TYPE_YES:
                return $ds->expr()->eq($fieldName, 'true');
            default:
                return $ds->expr()->neq($fieldName, 'true');
        }
    }
}
