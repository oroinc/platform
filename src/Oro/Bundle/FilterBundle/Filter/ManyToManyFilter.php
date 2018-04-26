<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ManyToManyFilterType;

class ManyToManyFilter extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new \LogicException(sprintf(
                '"Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter" expected but "%s" given.',
                get_class($ds)
            ));
        }

        return $this->createExpression($ds, $comparisonType);
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param string $type
     *
     * @return ExpressionBuilderInterface
     */
    protected function createExpression(OrmFilterDatasourceAdapter $ds, $type)
    {
        $joinIdentifier = $this->getJoinIdentifier($ds);
        switch ($type) {
            case FilterUtility::TYPE_EMPTY:
                return $ds->expr()->isNull($joinIdentifier);
            case FilterUtility::TYPE_NOT_EMPTY:
                return $ds->expr()->isNotNull($joinIdentifier);
        }

        throw new \LogicException('Type "%s" is not supported.');
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     *
     * @return string
     */
    protected function getJoinIdentifier(OrmFilterDatasourceAdapter $ds)
    {
        list($joinAlias, $class) = explode('.', $this->getOr(FilterUtility::DATA_NAME_KEY));
        $em = $ds->getQueryBuilder()->getEntityManager();
        $metadata = $em->getClassMetadata($class);
        $fieldNames = $metadata->getIdentifierFieldNames();
        if ($count = count($fieldNames) !== 1) {
            throw new \LogicException('Class needs to have exactly 1 identifier, but it has "%d"', $count);
        }

        return sprintf('%s.%s', $joinAlias, $fieldNames[0]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return ManyToManyFilterType::class;
    }
}
