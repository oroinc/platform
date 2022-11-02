<?php

namespace Oro\Bundle\DataAuditBundle\Strategy\Processor;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierHydrator;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * For entity to find relationship with edited parent entity.
 * LocalizedFallbackValue will find from a map of all entity extends AbstractLocalizedFallbackValue
 */
class DefaultEntityAuditStrategyProcessor implements EntityAuditStrategyProcessorInterface
{
    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @throws MappingException
     */
    public function processInverseCollections(array $sourceEntityData): array
    {
        $fieldData = [];
        $sourceEntityId = $sourceEntityData['entity_id'];
        $sourceEntityClass = $sourceEntityData['entity_class'];
        $sourceEntityManager = $this->doctrine->getManagerForClass($sourceEntityClass);
        $sourceEntityMeta = $sourceEntityManager->getClassMetadata($sourceEntityClass);
        $sourceEntity = $sourceEntityManager->find($sourceEntityClass, $sourceEntityId);

        if ($sourceEntity) {
            $fieldData = $this->processEntityAssociationsFromCollection(
                $sourceEntityMeta,
                $sourceEntity,
                $sourceEntityData
            );
        }

        return $fieldData;
    }

    /**
     * @throws MappingException
     */
    private function processEntityAssociationsFromCollection(
        ClassMetadata $sourceEntityMeta,
        object        $sourceEntity,
        array         $sourceEntityData
    ): ?array {
        $fieldsData = [];

        foreach (array_keys($sourceEntityMeta->associationMappings) as $sourceFieldName) {
            $targetEntityClass = $sourceEntityMeta->associationMappings[$sourceFieldName]['targetEntity'];
            $targetFieldName = $this->getTargetFieldName($sourceEntityMeta, $sourceFieldName);
            $value = $sourceEntityMeta->getFieldValue($sourceEntity, $sourceFieldName);
            $hasChangeSet = empty($sourceEntityData['change_set'][$sourceFieldName]);

            /**
             * $hasChangeSet - indicates whether this association be built first time (usual in entity creating).
             *      data audit for entity creating will be done in class AuditChangedEntitiesRelationsProcessor
             * $value - check the source entity does not belong to any collections.
             * $targetFieldName - check the unidirectional relation.
             */
            if (!$hasChangeSet || !$value || !$targetFieldName) {
                continue;
            }

            $entityIds = $this->getEntityIds($targetEntityClass, $value);
            if (!$entityIds) {
                continue;
            }

            $fieldsData[$sourceFieldName] = [
                'entity_class' => $targetEntityClass,
                'field_name' => $targetFieldName,
                'entity_ids' => $entityIds,
            ];
        }

        return $fieldsData;
    }

    private function getTargetFieldName(ClassMetadata $sourceEntityMeta, string $sourceFieldName): ?string
    {
        return $sourceEntityMeta->associationMappings[$sourceFieldName]['inversedBy']
            ?? $sourceEntityMeta->associationMappings[$sourceFieldName]['mappedBy']
            ?? null;
    }

    /**
     * @param string $entityClass
     * @param object $entity
     *
     * @return int[]|string[]
     * @throws MappingException
     */
    private function getEntityIds(string $entityClass, object $entity): array
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($entityClass);
        if ($entity instanceof PersistentCollection && !$entity->isInitialized()) {
            $mapping = $entity->getMapping();
            $class = $mapping['targetEntity'];
            $field = $mapping['mappedBy'] ?? null;
            $memberOf = $mapping['type'] & ClassMetadataInfo::MANY_TO_MANY;
            if ($field) {
                return $this->getIdsWithoutHydration($entityManager, $entity, $class, $field, $memberOf);
            }
        }

        if ($entity instanceof Collection) {
            return array_map(fn ($item) => $this->getEntityId($entityManager, $item), $entity->toArray());
        }

        if (is_object($entity)) {
            return [$this->getEntityId($entityManager, $entity)];
        }

        return [];
    }

    /**
     * @throws MappingException
     */
    private function getIdsWithoutHydration(
        EntityManagerInterface $entityManager,
        PersistentCollection $collection,
        string $class,
        string $field,
        bool $memberOf
    ): array {
        $entityManager->getConfiguration()->addCustomHydrationMode('IdentifierHydrator', IdentifierHydrator::class);

        $fieldName = $entityManager->getClassMetadata($class)->getSingleIdentifierFieldName();
        $select = QueryBuilderUtil::sprintf('e.%s as id', $fieldName);
        $where = QueryBuilderUtil::sprintf('e.%s', $field);

        $queryBuilder = $entityManager->getRepository($class)->createQueryBuilder('e');
        $queryBuilder->select($select);
        if ($memberOf) {
            $queryBuilder->where($queryBuilder->expr()->isMemberOf(':field', $where));
        } else {
            $queryBuilder->where($queryBuilder->expr()->eq($where, ':field'));
        }
        $queryBuilder->setParameter('field', $collection->getOwner());

        return $queryBuilder
            ->getQuery()
            ->getResult('IdentifierHydrator');
    }

    private function getEntityId(EntityManagerInterface $entityManager, object $entity): mixed
    {
        return $entityManager
            ->getClassMetadata(ClassUtils::getClass($entity))
            ->getSingleIdReflectionProperty()
            ?->getValue($entity);
    }

    public function processChangedEntities(array $sourceEntityData): array
    {
        return $sourceEntityData;
    }

    public function processInverseRelations(array $sourceEntityData): array
    {
        return $sourceEntityData;
    }
}
