<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;

use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

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
     * @param array $targets [entity class => [id, ...], ...]
     *
     * @return array [['id' => entity id, 'entity' => entity class, 'title' => entity title], ...]
     */
    public function getTitles(array $targets)
    {
        $result = [];
        $em = $this->getEntityManager($targets);
        if (null !== $em) {
            $queries = $this->getNameQueries($em, $targets);
            foreach ($queries as $query) {
                $result = array_merge($result, $query->getArrayResult());
            }
        }

        return $result;
    }

    /**
     * @param EntityManager $em
     * @param array         $targets [entity class => [id, ...], ...]
     *
     * @return AbstractQuery[]
     */
    private function getNameQueries(EntityManager $em, $targets)
    {
        $queries = [];
        $groups = $this->groupByIdentifierType($em, $targets);
        foreach ($groups as $group) {
            if (count($group) === 1) {
                list($idFieldName, $ids) = reset($group);
                $entityClass = key($group);
                $queries[] = $this->getNameQuery($em, $entityClass, $idFieldName, $ids);
            } else {
                $queries[] = $this->getNameUnionQuery($em, $group);
            }
        }

        return $queries;
    }

    /**
     * @param EntityManager $em
     * @param string        $entityClass
     * @param string        $idFieldName
     * @param array         $ids
     *
     * @return Query
     */
    private function getNameQuery(EntityManager $em, $entityClass, $idFieldName, array $ids)
    {
        $qb = $em->getRepository($entityClass)
            ->createQueryBuilder('e')
            ->select(
                sprintf(
                    'e.%s AS id, \'%s\' AS entity, %s AS title',
                    $idFieldName,
                    $entityClass,
                    $this->entityNameResolver->prepareNameDQL(
                        $this->entityNameResolver->getNameDQL($entityClass, 'e'),
                        true
                    )
                )
            );
        $qb->andWhere($qb->expr()->in('e.' . $idFieldName, $ids));

        return $qb->getQuery();
    }

    /**
     * @param EntityManager $em
     * @param array         $targets [entity class => [entity id field name, id, ...], ...]
     *
     * @return AbstractQuery
     */
    private function getNameUnionQuery(EntityManager $em, array $targets)
    {
        $qb = new UnionQueryBuilder($em);
        $qb
            ->addSelect('id', 'id', Type::INTEGER)
            ->addSelect('entity', 'entity')
            ->addSelect('title', 'title');
        foreach ($targets as $entityClass => $group) {
            $qb->addSubQuery(
                $this->getNameQuery($em, $entityClass, $group[0], $group[1])
            );
        }

        return $qb->getQuery();
    }

    /**
     * @param EntityManager $em
     * @param array         $targets [entity class => [id, ...], ...]
     *
     * @return array
     *  [
     *      entity id type => [
     *          entity class => [
     *              entity id field name,
     *              [id, ...]
     *          ],
     *          ...],
     *      ...
     *  ]
     */
    private function groupByIdentifierType(EntityManager $em, array $targets)
    {
        $groups = [];
        foreach ($targets as $entityClass => $ids) {
            if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
                continue;
            }
            $metadata = $this->getEntityMetadata($em, $entityClass);
            if (null === $metadata) {
                continue;
            }
            $idFieldNames = $metadata->getIdentifierFieldNames();
            if (count($idFieldNames) !== 1) {
                continue;
            }
            $idFieldName = reset($idFieldNames);
            $idFieldType = $metadata->getTypeOfField($idFieldName);
            $groups[$idFieldType][$entityClass] = [$idFieldName, $ids];
        }

        return $groups;
    }

    /**
     * @param array $targets [entity class => [id, ...], ...]
     *
     * @return EntityManager|null
     */
    protected function getEntityManager(array $targets)
    {
        $em = null;
        foreach ($targets as $entityClass => $ids) {
            $em = $this->doctrineHelper->getEntityManagerForClass($entityClass, false);
            if (null !== $em) {
                break;
            }
        }

        return $em;
    }

    /**
     * @param EntityManager $em
     * @param string        $entityClass
     *
     * @return ClassMetadata|null
     */
    protected function getEntityMetadata(EntityManager $em, $entityClass)
    {
        try {
            return $em->getClassMetadata($entityClass);
        } catch (MappingException $e) {
            return null;
        }
    }
}
