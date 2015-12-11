<?php

namespace Oro\Bundle\TagBundle\Filter;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query\Expr\Func;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;

abstract class AbstractTagsFilter extends AbstractFilter
{
    /**
     * Returns the class name of an entity for which filtering will be applied.
     *
     * @return string
     */
    abstract protected function getEntityClassName();

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $this->checkDataSourceAdapter($ds);
        /** @var OrmFilterDatasourceAdapter $ds */

        $data = $this->parseData($data);
        if ($data !== false) {
            $filterExpr = $this->buildFilterExpr($ds, $data);
            if (false !== $filterExpr) {
                $this->applyFilterToClause($ds, $filterExpr);
                $className = $this->getEntityClassName();
                $ds->setParameter('tag_filter_entity_class_name', $className);

                return true;
            }
        }

        return false;
    }

    /**
     * Builds filtering expression by tags ids and entity class name
     *
     * @param OrmFilterDatasourceAdapter $ds
     * @param array                      $data
     *
     * @return bool| Func
     */
    protected function buildFilterExpr(OrmFilterDatasourceAdapter $ds, array $data)
    {
        $expr = false;

        $qb            = $ds->getQueryBuilder();
        $entityIdAlias = $qb->getRootAliases()[0] . '.id';

        $taggingAlias = $ds->generateParameterName('tagging');
        $tagAlias     = $ds->generateParameterName('tag');

        $subQueryDQL = $qb->getEntityManager()->getRepository('OroTagBundle:Tagging')
            ->createQueryBuilder($taggingAlias)
            ->select($taggingAlias . '.recordId')
            ->join($taggingAlias . '.tag', $tagAlias)
            ->where($taggingAlias . '.entityName = :tag_filter_entity_class_name')
            ->andWhere($qb->expr()->in($tagAlias . '.id', $data['value']))
            ->getDQL();

        switch ($data['type']) {
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

        if ($value instanceof Collection) {
            $value = $value->getValues();
        }

        if (!is_array($value)) {
            return false;
        }

        $data['type']  = isset($data['type']) ? $data['type'] : DictionaryFilterType::TYPE_IN;
        $data['value'] = $value;

        return $data;
    }
}
