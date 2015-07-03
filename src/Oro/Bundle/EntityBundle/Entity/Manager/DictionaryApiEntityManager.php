<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;
use Oro\Bundle\EntityBundle\ORM\QueryBuilderHelper;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class DictionaryApiEntityManager extends ApiEntityManager
{
    /** @var ChainDictionaryValueListProvider */
    protected $dictionaryProvider;

    /**
     * @param ObjectManager                    $om
     * @param ChainDictionaryValueListProvider $dictionaryProvider
     */
    public function __construct(ObjectManager $om, ChainDictionaryValueListProvider $dictionaryProvider)
    {
        parent::__construct(null, $om);
        $this->dictionaryProvider = $dictionaryProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSerializationConfig()
    {
        $this->dictionaryProvider->getSerializationConfig($this->class);
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $qb = $this->dictionaryProvider->getValueListQueryBuilder($this->class);
        if ($qb instanceof QueryBuilder) {
            $criteria = $this->prepareQueryCriteria($limit, $page, $criteria, $orderBy);
            $this->applyJoins($qb, $joins);

            // fix of doctrine error with Same Field, Multiple Values, Criteria and QueryBuilder
            // http://www.doctrine-project.org/jira/browse/DDC-2798
            // TODO revert changes when doctrine version >= 2.5 in scope of BAP-5577
            QueryBuilderHelper::addCriteria($qb, $criteria);
            // $qb->addCriteria($criteria);
        } elseif ($qb instanceof SqlQueryBuilder) {
            $qb->setMaxResults($limit);
            $qb->setFirstResult($this->getOffset($page, $limit));
            $qb->orderBy($orderBy);
        } elseif (null !== $qb) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected instance of Doctrine\ORM\QueryBuilder'
                    . ' or Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder, "%s" given',
                    is_object($qb) ? get_class($qb) : gettype($qb)
                )
            );
        }

        return $qb;
    }
}
