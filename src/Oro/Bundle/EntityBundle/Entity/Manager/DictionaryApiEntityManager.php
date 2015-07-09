<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;

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
        return $this->dictionaryProvider->getSerializationConfig($this->class);
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $qb = $this->dictionaryProvider->getValueListQueryBuilder($this->class);
        if ($qb instanceof QueryBuilder) {
            if ($limit >= 0) {
                $qb->setMaxResults($limit);
                $qb->setFirstResult($this->getOffset($page, $limit));
            }
            if ($orderBy) {
                $qb->orderBy($orderBy);
            }
        } elseif (null !== $qb) {
            throw new \RuntimeException(
                sprintf(
                    'Expected instance of Doctrine\ORM\QueryBuilder, "%s" given.',
                    is_object($qb) ? get_class($qb) : gettype($qb)
                )
            );
        }

        return $qb;
    }
}
