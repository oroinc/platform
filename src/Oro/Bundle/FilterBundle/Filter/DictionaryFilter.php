<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryBuilderTools;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;

use LogicException;

class DictionaryFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $type = $data['type'];

        $parameterName = $ds->generateParameterName($this->getName());

        $this->applyFilterToClause(
            $ds,
            $this->buildComparisonExpr(
                $ds,
                $type,
                $this->getFilteredFieldName($ds),
                $parameterName
            )
        );

        if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
            $ds->setParameter($parameterName, $data['value']);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return DictionaryFilterType::NAME;
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    protected function parseData($data)
    {
        $type = isset($data['type']) ? $data['type'] : null;
        if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])
            && (!is_array($data) || !array_key_exists('value', $data) || empty($data['value']))
        ) {
            return false;
        }

        $data['type']  = $type;
        $data['value'] = $this->parseValue($data['type'], $data['value']);

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
     * @return string
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $parameterName
    ) {
        return $ds->expr()->in($fieldName, $parameterName, true);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $fieldName
     *
     * @return bool
     */
    protected function isCompositeField(FilterDatasourceAdapterInterface $ds, $fieldName)
    {
        return (bool)preg_match('/(?<![\w:.])(CONCAT)\s*\(/im', $ds->getFieldByAlias($fieldName));
    }

    /**
     * Return a value depending on comparison type
     *
     * @param int    $comparisonType
     * @param string $value
     *
     * @return mixed
     */
    protected function parseValue($comparisonType, $value)
    {
        switch ($comparisonType) {
            case TextFilterType::TYPE_CONTAINS:
            case TextFilterType::TYPE_NOT_CONTAINS:
                return sprintf('%%%s%%', $value);
            case TextFilterType::TYPE_STARTS_WITH:
                return sprintf('%s%%', $value);
            case TextFilterType::TYPE_ENDS_WITH:
                return sprintf('%%%s', $value);
            case TextFilterType::TYPE_IN:
            case TextFilterType::TYPE_NOT_IN:
                return array_map('trim', explode(',', $value));
            default:
                return $value;
        }
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @return string
     */
    protected function getFilteredFieldName(FilterDatasourceAdapterInterface $ds)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new LogicException(
                sprintf(
                    '"Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter" expected but "%s" given.',
                    get_class($ds)
                )
            );
        }
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        list($joinAlias, $field) = explode('.', $fieldName);
        $qb = $ds->getQueryBuilder();
        $em = $qb->getEntityManager();
        $class = $this->get('class');
        $metadata = $em->getClassMetadata($class);
        $fieldNames = $metadata->getIdentifierFieldNames();
        if ($count = count($fieldNames) !== 1) {
            throw new LogicException('Class needs to have exactly 1 identifier, but it has "%d"', $count);
        }
        $field = sprintf('%s.%s', $joinAlias, $fieldNames[0]);

        return $field;
    }
}
