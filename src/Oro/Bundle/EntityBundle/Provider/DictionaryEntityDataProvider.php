<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Translatable\Translatable;
use Oro\Bundle\EntityBundle\Exception\RuntimeException;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;

/**
 * Provides values for dictionaries and enums.
 */
class DictionaryEntityDataProvider
{
    private const DEFAULT_SEARCH_FIELD = 'label';
    private const SEARCH_FIELD_FOR_ENUM = 'name';

    private ManagerRegistry $doctrine;
    private ChainDictionaryValueListProvider $dictionaryProvider;
    private ConfigManager $entityConfigManager;
    private EntityClassNameHelper $entityClassNameHelper;
    private EntityNameResolver $entityNameResolver;
    private AclHelper $aclHelper;
    private QueryHintResolverInterface $queryHintResolver;
    private array $additionalDictionaries = [];

    public function __construct(
        ManagerRegistry $doctrine,
        ChainDictionaryValueListProvider $dictionaryProvider,
        ConfigManager $entityConfigManager,
        EntityClassNameHelper $entityClassNameHelper,
        EntityNameResolver $entityNameResolver,
        AclHelper $aclHelper,
        QueryHintResolverInterface $queryHintResolver
    ) {
        $this->doctrine = $doctrine;
        $this->dictionaryProvider = $dictionaryProvider;
        $this->entityConfigManager = $entityConfigManager;
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->entityNameResolver = $entityNameResolver;
        $this->aclHelper = $aclHelper;
        $this->queryHintResolver = $queryHintResolver;
    }

    /**
     * Registers an entity as a dictionary when it is not marked as a dictionary in entity config.
     */
    public function registerDictionary(string $entityClass, array $searchFieldNames): void
    {
        $this->additionalDictionaries[$entityClass] = $searchFieldNames;
    }

    public function getValuesBySearchQuery(string $entityName, ?string $searchQuery): array
    {
        $entityClass = $this->resolveEntityClass($entityName);
        $qb = $this->getQueryBuilder($entityClass);
        $em = $qb->getEntityManager();
        $metadata = $em->getClassMetadata($entityClass);
        if ($searchQuery) {
            $searchFields = $this->getSearchFields($metadata);
            foreach ($searchFields as $searchField) {
                $qb->orWhere('LOWER(e.' . $searchField . ') LIKE LOWER(:search_value)');
            }
            $qb->setParameter('search_value', '%' . $searchQuery . '%');
        }

        return $this->loadValues($qb, $metadata);
    }

    public function getValuesByIds(string $entityName, array $ids): array
    {
        $entityClass = $this->resolveEntityClass($entityName);
        $qb = $this->getQueryBuilder($entityClass);
        $metadata = $qb->getEntityManager()->getClassMetadata($entityClass);
        $qb->andWhere('e.' . $this->getEntityIdentifierFieldName($metadata) . ' in (:ids)');
        $qb->setParameter('ids', $ids);

        return $this->loadValues($qb, $metadata);
    }

    private function resolveEntityClass(string $entityName): string
    {
        $entityClass = $this->entityClassNameHelper->resolveEntityClass($entityName, true);
        if (!isset($this->additionalDictionaries[$entityClass])
            && !\in_array($entityClass, $this->dictionaryProvider->getSupportedEntityClasses(), true)
        ) {
            throw new RuntimeException(sprintf('The "%s" entity is not supported.', $entityClass));
        }

        return $entityClass;
    }

    private function getQueryBuilder(string $entityClass): QueryBuilder
    {
        if (isset($this->additionalDictionaries[$entityClass])) {
            /** @var EntityManagerInterface $em */
            $em = $this->doctrine->getManagerForClass($entityClass);

            return $em->createQueryBuilder()->select('e')->from($entityClass, 'e');
        }

        $qb = $this->dictionaryProvider->getValueListQueryBuilder($entityClass);
        if (null !== $qb) {
            return $qb;
        }

        throw new \LogicException(sprintf('Cannot get a query builder for the "%s" entity.', $entityClass));
    }

    private function loadValues(QueryBuilder $qb, ClassMetadata $metadata): array
    {
        $query = $this->aclHelper->apply($qb);
        if (is_a($metadata->getName(), Translatable::class, true)) {
            $this->queryHintResolver->resolveHints($query, ['HINT_TRANSLATABLE']);
        }

        return $this->prepareData($query->getResult(), $metadata);
    }

    private function prepareData(array $entities, ClassMetadata $metadata): array
    {
        $prepared = [];
        foreach ($entities as $entity) {
            $id = $this->getEntityIdentifier($entity, $metadata);
            $prepared[] = ['id' => $id, 'value' => $id, 'text' => $this->entityNameResolver->getName($entity)];
        }

        return $prepared;
    }

    private function getSearchFields(ClassMetadata $metadata): array
    {
        $className = $metadata->getName();
        if (isset($this->additionalDictionaries[$className])) {
            return $this->additionalDictionaries[$className];
        }

        if (is_a($className, AbstractEnumValue::class, true)) {
            return [self::SEARCH_FIELD_FOR_ENUM];
        }

        if ($this->entityConfigManager->hasConfig($className)) {
            $entityConfig = $this->entityConfigManager->getEntityConfig('dictionary', $className);
            $searchFieldNames = $entityConfig->get('search_fields');
            if ($searchFieldNames) {
                return $searchFieldNames;
            }
        }

        if ($metadata->hasField(self::DEFAULT_SEARCH_FIELD)) {
            return [self::DEFAULT_SEARCH_FIELD];
        }

        throw new \LogicException(sprintf(
            'Search fields are not configured for the "%s" entity.',
            $metadata->getName()
        ));
    }

    private function getEntityIdentifierFieldName(ClassMetadata $metadata): string
    {
        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (\count($idFieldNames) !== 1) {
            throw new RuntimeException(sprintf(
                'The primary key for the "%s" entity is absent or contains more than one field.',
                $metadata->getName()
            ));
        }

        return reset($idFieldNames);
    }

    private function getEntityIdentifier(object $entity, ClassMetadata $metadata): mixed
    {
        $entityIdentifier = $metadata->getIdentifierValues($entity);
        if (\count($entityIdentifier) !== 1) {
            throw new RuntimeException(sprintf(
                'The primary key for the "%s" entity is absent or contains more than one field.',
                $metadata->getName()
            ));
        }

        return reset($entityIdentifier);
    }
}
