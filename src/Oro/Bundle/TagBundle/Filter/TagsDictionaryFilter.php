<?php

namespace Oro\Bundle\TagBundle\Filter;

use Doctrine\ORM\Query\Expr\Func;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * This class implements logic for tags filter, based on choice-tree filter.
 */
class TagsDictionaryFilter extends DictionaryFilter
{
    /**
     * {@inheritdoc}
     */
    protected $joinOperators = [
        DictionaryFilterType::TYPE_NOT_IN => DictionaryFilterType::TYPE_IN,
    ];

    /**
     * {@inheritdoc}
     * @param OrmFilterDatasourceAdapter $ds
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $this->checkDataSourceAdapter($ds);

        $className = $this->getEntityClassName();
        $entityClassParam = 'tags_filter_entity_class_' . $this->clearEntityClassName($className);
        $filterExpr = $this->buildFilterExpr($ds, $data, $entityClassParam, $comparisonType);
        if (false !== $filterExpr) {
            $ds->setParameter($entityClassParam, $className);
        }

        return $filterExpr;
    }

    /**
     * Builds filtering expression by tags ids and entity class name
     *
     * @param OrmFilterDatasourceAdapter $ds
     * @param array                      $data
     * @param string                     $entityClassParam
     * @param string                     $comparisonType
     *
     * @return bool|Func
     */
    protected function buildFilterExpr(OrmFilterDatasourceAdapter $ds, array $data, $entityClassParam, $comparisonType)
    {
        QueryBuilderUtil::checkIdentifier($entityClassParam);
        $expr = false;

        if (empty($data['value'])) {
            return $expr;
        }

        $qb            = $ds->getQueryBuilder();
        $entityIdAlias = $this->getDataFieldName();

        $taggingAlias = $ds->generateParameterName('tagging');
        $tagAlias     = $ds->generateParameterName('tag');

        $subQueryDQL = $qb->getEntityManager()->getRepository('OroTagBundle:Tagging')
            ->createQueryBuilder($taggingAlias)
            ->select($taggingAlias . '.recordId')
            ->join($taggingAlias . '.tag', $tagAlias)
            ->where(sprintf('%s.entityName = :%s', $taggingAlias, $entityClassParam))
            ->andWhere($qb->expr()->in($tagAlias . '.id', $data['value']))
            ->getDQL();

        switch ($comparisonType) {
            case DictionaryFilterType::TYPE_IN:
                $expr = $ds->expr()->in($entityIdAlias, $subQueryDQL);
                break;
            case DictionaryFilterType::TYPE_NOT_IN:
                $expr = $ds->expr()->notIn($entityIdAlias, $subQueryDQL);
                break;
            default:
                break;
        }

        return $expr;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     */
    protected function checkDataSourceAdapter(FilterDatasourceAdapterInterface $ds)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new \LogicException(
                sprintf(
                    '"Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter" expected but "%s" given.',
                    get_class($ds)
                )
            );
        }
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    protected function parseData($data)
    {
        if (!isset($data['value']) || empty($data['value'])) {
            return false;
        }
        $value = $data['value'];

        if (!is_array($value)) {
            return false;
        }

        $data['type']  = isset($data['type']) ? $data['type'] : DictionaryFilterType::TYPE_IN;
        $data['value'] = $value;

        return $data;
    }

    /**
     * Clears entity's class name.
     *
     * @param string $entityClassName
     *
     * @return string
     */
    protected function clearEntityClassName($entityClassName)
    {
        return str_replace('\\', '_', $entityClassName);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return $this->params['entity_class'];
    }
}
