<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * The API manager for dictionaries and enums.
 */
class DictionaryApiEntityManager extends ApiEntityManager
{
    private ChainDictionaryValueListProvider $dictionaryProvider;

    public function __construct(
        ObjectManager $om,
        ChainDictionaryValueListProvider $dictionaryProvider
    ) {
        parent::__construct(null, $om);
        $this->dictionaryProvider = $dictionaryProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveEntityClass($entityName, $isPluralAlias = false)
    {
        try {
            $entityClass = $this->entityClassNameHelper->resolveEntityClass($entityName, $isPluralAlias);
        } catch (EntityAliasNotFoundException $e) {
            $entityClass = null;
        }
        if ($entityClass && !in_array($entityClass, $this->dictionaryProvider->getSupportedEntityClasses(), true)) {
            $entityClass = null;
        }

        return $entityClass;
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
                QueryBuilderUtil::checkField($orderBy);
                $qb->orderBy($orderBy);
            }
        } elseif (null !== $qb) {
            throw new \RuntimeException(sprintf(
                'Expected instance of Doctrine\ORM\QueryBuilder, "%s" given.',
                get_debug_type($qb)
            ));
        }

        return $qb;
    }
}
