<?php

namespace Oro\Bundle\CronBundle\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\CronBundle\ORM\CommandArgsTokenizer;
use Oro\Bundle\EntityBundle\ORM\QueryUtils;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class CommandWithArgsFilter extends StringFilter
{
    /** @var CommandArgsTokenizer */
    protected $tokenizer;

    /** @var FilterDatasourceAdapterInterface */
    protected $ds;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     * @param CommandArgsTokenizer $tokenizer
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        CommandArgsTokenizer $tokenizer
    ) {
        parent::__construct($factory, $util);
        $this->tokenizer = $tokenizer;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $this->ds = $ds;
        $data     = $this->parseData($data);
        $this->ds = null;

        if (!$data) {
            return false;
        }

        $type = $data['type'];

        $values = is_array($data['value']) ? $data['value'] : [$data['value']];
        foreach ($values as $value) {
            $parameterName = $ds->generateParameterName($this->getName());
            $this->applyFilterToClause(
                $ds,
                $this->buildComparisonExpr(
                    $ds,
                    $type,
                    $this->getFieldExpr($type),
                    $parameterName
                )
            );
            if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
                $ds->setParameter($parameterName, $value);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseValue($comparisonType, $value)
    {
        switch ($comparisonType) {
            case TextFilterType::TYPE_CONTAINS:
            case TextFilterType::TYPE_NOT_CONTAINS:
                // a value for CONTAINS and NOT CONTAINS may contains several parts
                return $this->getValueParts($comparisonType, $value);
            default:
                return parent::parseValue($comparisonType, $value);
        }
    }

    /**
     * Returns an DQL expression the filter should be applied to
     *
     * @param int $comparisonType
     *
     * @return string
     */
    protected function getFieldExpr($comparisonType)
    {
        $dataName = array_map(
            'trim',
            preg_split('/,/', $this->get(FilterUtility::DATA_NAME_KEY), -1, PREG_SPLIT_NO_EMPTY)
        );
        switch ($comparisonType) {
            case TextFilterType::TYPE_CONTAINS:
            case TextFilterType::TYPE_NOT_CONTAINS:
                // CONTAINS and NOT CONTAINS should search in all field
                return QueryUtils::buildConcatExpr($dataName);
            default:
                // other comparisons should work only for the first column
                return reset($dataName);
        }
    }

    /**
     * @param int    $comparisonType
     * @param string $value
     *
     * @return array
     */
    protected function getValueParts($comparisonType, $value)
    {
        return array_map(
            function ($val) use ($comparisonType) {
                return parent::parseValue($comparisonType, $val);
            },
            $this->tokenizer->tokenize($value, $this->ds->getDatabasePlatform())
        );
    }
}
