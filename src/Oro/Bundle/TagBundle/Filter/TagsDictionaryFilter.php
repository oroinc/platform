<?php

namespace Oro\Bundle\TagBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * The filter by tags.
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
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, OrmFilterDatasourceAdapter::class);
        }

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
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function buildFilterExpr(OrmFilterDatasourceAdapter $ds, array $data, $entityClassParam, $comparisonType)
    {
        QueryBuilderUtil::checkIdentifier($entityClassParam);
        $expr = false;

        $qb = $ds->getQueryBuilder();
        $entityIdAlias = $this->getDataFieldName();

        $taggingAlias = QueryBuilderUtil::generateParameterName('tagging');
        $tagAlias = QueryBuilderUtil::generateParameterName('tag');

        $taggingRepository = $qb->getEntityManager()->getRepository(Tagging::class);
        if (!$this->isValueRequired($data['type'])) {
            $subQueryDQL = $taggingRepository->createQueryBuilder($taggingAlias)
                ->select(QueryBuilderUtil::getField($taggingAlias, 'id'))
                ->where(QueryBuilderUtil::sprintf('%s.entityName = :%s', $taggingAlias, $entityClassParam))
                ->andWhere(QueryBuilderUtil::sprintf('%s.recordId = %s', $taggingAlias, $entityIdAlias))
                ->getDQL();
        } elseif (isset($data['value']) && '' !== $data['value']) {
            $subQueryDQL = $taggingRepository->createQueryBuilder($taggingAlias)
                ->select(QueryBuilderUtil::getField($taggingAlias, 'recordId'))
                ->join(QueryBuilderUtil::getField($taggingAlias, 'tag'), $tagAlias)
                ->where(QueryBuilderUtil::sprintf('%s.entityName = :%s', $taggingAlias, $entityClassParam))
                ->andWhere($qb->expr()->in(QueryBuilderUtil::getField($tagAlias, 'id'), $data['value']))
                ->getDQL();
        } else {
            return $expr;
        }

        switch ($comparisonType) {
            case DictionaryFilterType::TYPE_IN:
            case DictionaryFilterType::EQUAL:
                $expr = $ds->expr()->in($entityIdAlias, $subQueryDQL);
                break;
            case DictionaryFilterType::TYPE_NOT_IN:
            case DictionaryFilterType::NOT_EQUAL:
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
        return $this->get('entity_class');
    }
}
