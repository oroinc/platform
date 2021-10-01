<?php

namespace Oro\Bundle\EntityBundle\Entity;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;

/**
 * Doctrine repository for EntityFieldFallbackValue entity
 */
class EntityFieldFallbackValueRepository extends EntityRepository
{
    /**
     * @param object $entity
     * @param string[] $fields
     * @return EntityFieldFallbackValue[] array like ['fieldName' => EntityFieldFallbackValue]
     */
    public function findByEntityFields($entity, array $fields): array
    {
        if (!$fields) {
            return [];
        }

        $entityClass = ClassUtils::getClass($entity);
        $metadata = $this->getEntityManager()->getClassMetadata($entityClass);

        $selectExpression = [];
        foreach ($fields as $fieldName) {
            if (!$metadata->hasAssociation($fieldName)) {
                throw new \InvalidArgumentException("Entity '$entityClass' does not have association '$fieldName'");
            }
            $selectExpression[] = "IDENTITY(en.$fieldName) AS $fieldName";
        }

        $qb = $this->getEntityManager()
            ->getRepository($entityClass)
            ->createQueryBuilder('en');

        $ids = array_filter($qb->select($selectExpression)
            ->where($qb->expr()->eq('en', ':entity'))
            ->setParameter('entity', $entity)
            ->getQuery()
            ->getSingleResult());

        $idsToFields = array_flip($ids);
        $result = [];
        /** @var EntityFieldFallbackValue $value */
        foreach ($this->findBy(['id' => $ids]) as $value) {
            $fieldName = $idsToFields[$value->getId()];
            $result[$fieldName] = $value;
        }
        return $result;
    }

    /**
     * Checks whether an EntityFieldFallbackValue with the given ID is assigned
     * to the given field of the given entity and if so, returns the ID of this entity object.
     */
    public function findEntityId(string $entityClass, string $fieldName, int $entityFieldFallbackValueId): ?int
    {
        $rows = $this->getEntityManager()
            ->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select('e.id')
            ->where(sprintf('e.%s = :value', $fieldName))
            ->setParameter('value', $entityFieldFallbackValueId)
            ->getQuery()
            ->getArrayResult();

        return $rows[0]['id'] ?? null;
    }
}
