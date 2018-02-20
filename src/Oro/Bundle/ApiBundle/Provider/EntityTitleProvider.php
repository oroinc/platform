<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;

/**
 * Provides a functionality to load a text representation of manageable (ORM) entities.
 */
class EntityTitleProvider
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntityNameResolver */
    private $entityNameResolver;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityNameResolver $entityNameResolver
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * Returns a text representation of entities.
     *
     * @param array $targets [entity class => [entity id field name, [entity id, ...]], ...]
     *                       The entity id field name can be:
     *                       a string for entities with single field identifier
     *                       an array of strings for entities with composite identifier
     *
     * @return array [['id' => entity id, 'entity' => entity class, 'title' => entity title], ...]
     */
    public function getTitles(array $targets)
    {
        $result = [];
        $em = $this->getEntityManager($targets);
        if (null !== $em) {
            $result = $this->loadTitles($em, $targets);
        }

        return $result;
    }

    /**
     * @param EntityManager $em
     * @param array         $targets [entity class => [entity id field name, [entity id, ...]], ...]
     *
     * @return AbstractQuery[]
     */
    private function loadTitles(EntityManager $em, $targets)
    {
        $result = [];
        $groups = $this->groupByIdentifierType($em, $targets);
        foreach ($groups as $idFieldType => $group) {
            if ('array' === $idFieldType) {
                foreach ($group as $entityClass => list($idFieldName, $ids)) {
                    $result = array_merge(
                        $result,
                        $this->executeQueryWithCompositeId(
                            $this->getNameQuery($em, $entityClass, $idFieldName, $ids),
                            $idFieldName
                        )
                    );
                }
            } elseif (count($group) === 1) {
                list($idFieldName, $ids) = reset($group);
                $entityClass = key($group);
                $result = array_merge(
                    $result,
                    $this->getNameQuery($em, $entityClass, $idFieldName, $ids)->getArrayResult()
                );
            } else {
                $result = array_merge(
                    $result,
                    $this->getNameUnionQuery($em, $group, $idFieldType)->getArrayResult()
                );
            }
        }

        return $result;
    }

    /**
     * @param AbstractQuery $query
     * @param string[]      $idFieldNames
     *
     * @return array [['id' => entity id, 'entity' => entity class, 'title' => entity title], ...]
     */
    private function executeQueryWithCompositeId(AbstractQuery $query, array $idFieldNames)
    {
        $result = [];

        $rows = $query->getArrayResult();
        foreach ($rows as $row) {
            $item = ['entity' => $row['entity'], 'title' => $row['title']];
            $id = [];
            $i = 0;
            foreach ($idFieldNames as $fieldName) {
                $i++;
                $id[$fieldName] = $row[sprintf('id%s', $i)];
            }
            $item['id'] = $id;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param EntityManager $em
     * @param string        $entityClass
     * @param string|array  $idFieldName
     * @param array         $ids
     *
     * @return Query
     */
    private function getNameQuery(EntityManager $em, $entityClass, $idFieldName, array $ids)
    {
        $qb = $em->getRepository($entityClass)->createQueryBuilder('e');
        $qb
            ->select(
                (string)$qb->expr()->literal($entityClass) . ' AS entity',
                $this->entityNameResolver->prepareNameDQL(
                    $this->entityNameResolver->getNameDQL($entityClass, 'e'),
                    true
                ) . ' AS title'
            );
        if (\is_array($idFieldName)) {
            $i = 0;
            foreach ($idFieldName as $fieldName) {
                $i++;
                $qb->addSelect(QueryBuilderUtil::sprintf('e.%s AS id%d', $fieldName, $i));
            }
            foreach ($ids as $id) {
                $expressions = $qb->expr()->andX();
                $i = 0;
                foreach ($idFieldName as $fieldName) {
                    $expressions->add(
                        $qb->expr()->eq(
                            QueryBuilderUtil::getField('e', $fieldName),
                            $qb->expr()->literal($id[$i])
                        )
                    );
                    $i++;
                }
                $qb->orWhere($expressions);
            }
        } else {
            $qb->addSelect(QueryBuilderUtil::sprintf('e.%s AS id', $idFieldName));
            $qb->andWhere($qb->expr()->in(QueryBuilderUtil::getField('e', $idFieldName), $ids));
        }

        return $qb->getQuery();
    }

    /**
     * @param EntityManager $em
     * @param array         $targets [entity class => [entity id field name, id, ...], ...]
     * @param string        $idFieldType
     *
     * @return AbstractQuery
     */
    private function getNameUnionQuery(EntityManager $em, array $targets, $idFieldType)
    {
        $qb = new UnionQueryBuilder($em);
        $qb
            ->addSelect('id', 'id', $idFieldType)
            ->addSelect('entity', 'entity')
            ->addSelect('title', 'title');
        foreach ($targets as $entityClass => list($idFieldName, $ids)) {
            $qb->addSubQuery(
                $this->getNameQuery($em, $entityClass, $idFieldName, $ids)
            );
        }

        return $qb->getQuery();
    }

    /**
     * @param EntityManager $em
     * @param array         $targets [entity class => [entity id field name, [entity id, ...]], ...]
     *
     * @return array
     *  [
     *      entity id type => [
     *          entity class => [entity id field name, [id, ...]],
     *          ...
     *      ],
     *      ...
     *  ]
     */
    private function groupByIdentifierType(EntityManager $em, array $targets)
    {
        $groups = [];
        foreach ($targets as $entityClass => list($idFieldName, $ids)) {
            if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
                continue;
            }
            if (is_array($idFieldName)) {
                $idFieldType = 'array';
            } else {
                $idFieldType = $this->doctrineHelper->getFieldDataType(
                    $em->getClassMetadata($entityClass),
                    $idFieldName
                );
            }
            if ($idFieldType) {
                $groups[$idFieldType][$entityClass] = [$idFieldName, $ids];
            }
        }

        return $groups;
    }

    /**
     * @param array $targets [entity class => [entity id field name, [entity id, ...]], ...]
     *
     * @return EntityManager|null
     */
    private function getEntityManager(array $targets)
    {
        $em = null;
        foreach ($targets as $entityClass => $value) {
            $em = $this->doctrineHelper->getEntityManagerForClass($entityClass, false);
            if (null !== $em) {
                break;
            }
        }

        return $em;
    }
}
