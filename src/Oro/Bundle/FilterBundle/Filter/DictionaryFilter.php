<?php

namespace Oro\Bundle\FilterBundle\Filter;

use LogicException;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;

class DictionaryFilter extends BaseMultiChoiceFilter
{
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
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $parameterName = $ds->generateParameterName($this->getName());
        if (!in_array($comparisonType, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY], true)) {
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
        return DictionaryFilterType::NAME;
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
     * @throws LogicException
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

        try {
            $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
            list($joinAlias) = explode('.', $fieldName);
            $qb = $ds->getQueryBuilder();
            $em = $qb->getEntityManager();
            $class = $this->get('options')['class'];
            $metadata = $em->getClassMetadata($class);
            $fieldNames = $metadata->getIdentifierFieldNames();
            if ($count = count($fieldNames) !== 1) {
                throw new LogicException('Class needs to have exactly 1 identifier, but it has "%d"', $count);
            }
            $field = sprintf('%s.%s', $joinAlias, $fieldNames[0]);
        } catch (\Exception $e) {
            $field = $this->getName();
        }

        return $field;
    }
}
