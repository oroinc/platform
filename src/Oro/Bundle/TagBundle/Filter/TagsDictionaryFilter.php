<?php

namespace Oro\Bundle\TagBundle\Filter;

use Doctrine\ORM\Query\Expr\Func;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * This class implements logic for tags filter, based on choice-tree filter.
 */
class TagsDictionaryFilter extends DictionaryFilter
{
    /** @var array */
    protected $emptyFilterTypes = [
        FilterUtility::TYPE_EMPTY,
        FilterUtility::TYPE_NOT_EMPTY
    ];

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

        $qb            = $ds->getQueryBuilder();
        $entityIdAlias = $this->getDataFieldName();

        $taggingAlias = QueryBuilderUtil::generateParameterName('tagging');
        $tagAlias     = QueryBuilderUtil::generateParameterName('tag');

        $taggingRepository = $qb->getEntityManager()->getRepository('OroTagBundle:Tagging');
        if (!in_array($data['type'], $this->emptyFilterTypes, true)) {
            if (empty($data['value'])) {
                return $expr;
            }

            $subQueryDQL = $taggingRepository->createQueryBuilder($taggingAlias)
                ->select(QueryBuilderUtil::getField($taggingAlias, 'recordId'))
                ->join(QueryBuilderUtil::getField($taggingAlias, 'tag'), $tagAlias)
                ->where(QueryBuilderUtil::sprintf('%s.entityName = :%s', $taggingAlias, $entityClassParam))
                ->andWhere($qb->expr()->in(QueryBuilderUtil::getField($tagAlias, 'id'), $data['value']))
                ->getDQL();
        } else {
            $subQueryDQL = $taggingRepository->createQueryBuilder($taggingAlias)
                ->select(QueryBuilderUtil::getField($taggingAlias, 'id'))
                ->where(QueryBuilderUtil::sprintf('%s.entityName = :%s', $taggingAlias, $entityClassParam))
                ->andWhere(QueryBuilderUtil::sprintf('%s.recordId = %s', $taggingAlias, $entityIdAlias))
                ->getDQL();
        }

        switch ($comparisonType) {
            case DictionaryFilterType::TYPE_IN:
                $expr = $ds->expr()->in($entityIdAlias, $subQueryDQL);
                break;
            case DictionaryFilterType::TYPE_NOT_IN:
                $expr = $ds->expr()->notIn($entityIdAlias, $subQueryDQL);
                break;
            case FilterUtility::TYPE_NOT_EMPTY:
                $expr = $ds->expr()->exists($subQueryDQL);
                break;
            case FilterUtility::TYPE_EMPTY:
                $expr = $ds->expr()->not($ds->expr()->exists($subQueryDQL));
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
        $type = $data['type'] ?? null;
        if (in_array($type, $this->emptyFilterTypes, true)) {
            return $data;
        }

        if (!isset($data['value']) || empty($data['value'])) {
            return false;
        }
        $value = $data['value'];

        if (!is_array($value)) {
            return false;
        }

        $data['type'] = $type ?: DictionaryFilterType::TYPE_IN;
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
