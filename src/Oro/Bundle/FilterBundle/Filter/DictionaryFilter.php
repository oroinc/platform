<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * The filter by an entity that is marked as a dictionary.
 */
class DictionaryFilter extends BaseMultiChoiceFilter
{
    const FILTER_TYPE_NAME = 'dictionary';

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
        if (isset($params['dictionary_code'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['dictionary_code'] = $params['dictionary_code'];
            unset($params['dictionary_code']);
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
            $this->getFilteredFieldName($ds),
            $parameterName
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return DictionaryFilterType::class;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isCompositeField(FilterDatasourceAdapterInterface $ds, $fieldName)
    {
        return (bool)preg_match('/(?<![\w:.])(CONCAT)\s*\(/im', $ds->getFieldByAlias($fieldName));
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     *
     * @return string
     *
     * @throws \LogicException
     */
    protected function getFilteredFieldName(FilterDatasourceAdapterInterface $ds)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, OrmFilterDatasourceAdapter::class);
        }

        try {
            $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
            [$joinAlias] = explode('.', $fieldName);

            $join = QueryBuilderUtil::findJoinByAlias($ds->getQueryBuilder(), $joinAlias);
            if ($join && $this->isToOne($ds)) {
                return $join->getJoin();
            }

            $qb = $ds->getQueryBuilder();
            $em = $qb->getEntityManager();
            $class = $this->get('options')['class'];
            $metadata = $em->getClassMetadata($class);
            $fieldNames = $metadata->getIdentifierFieldNames();

            $count = count($fieldNames);
            if ($count !== 1) {
                throw new \LogicException('Class needs to have exactly 1 identifier, but it has "%d"', $count);
            }
            $field = sprintf('%s.%s', $joinAlias, $fieldNames[0]);
        } catch (\Exception $e) {
            $field = $this->getName();
        }

        return $field;
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
        QueryBuilderUtil::checkField($fieldName);

        switch ($comparisonType) {
            case FilterUtility::TYPE_NOT_EMPTY:
                return $ds->expr()->isNotNull($fieldName);
            case FilterUtility::TYPE_EMPTY:
                return $ds->expr()->isNull($fieldName);
            default:
                return parent::buildComparisonExpr($ds, $comparisonType, $fieldName, $parameterName);
        }
    }
}
