<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\Exception\RuntimeException;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\TranslationBundle\Translation\TranslatableQueryTrait;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * The API manager for dictionaries and enums.
 */
class DictionaryApiEntityManager extends ApiEntityManager
{
    use TranslatableQueryTrait;

    private const DEFAULT_SEARCH_FIELD = 'label';
    private const SEARCH_FIELD_FOR_ENUM = 'name';

    /** @var ChainDictionaryValueListProvider */
    protected $dictionaryProvider;

    /** @var ConfigManager */
    protected $entityConfigManager;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /**
     * @param ObjectManager $om
     * @param ChainDictionaryValueListProvider $dictionaryProvider
     * @param ConfigManager $entityConfigManager
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(
        ObjectManager $om,
        ChainDictionaryValueListProvider $dictionaryProvider,
        ConfigManager $entityConfigManager,
        EntityNameResolver $entityNameResolver
    ) {
        parent::__construct(null, $om);
        $this->dictionaryProvider = $dictionaryProvider;
        $this->entityConfigManager = $entityConfigManager;
        $this->entityNameResolver = $entityNameResolver;
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
            throw new \RuntimeException(
                sprintf(
                    'Expected instance of Doctrine\ORM\QueryBuilder, "%s" given.',
                    is_object($qb) ? get_class($qb) : gettype($qb)
                )
            );
        }

        return $qb;
    }

    /**
     * Search entities by search query
     *
     * @param $searchQuery
     *
     * @return array
     */
    public function findValueBySearchQuery($searchQuery)
    {
        $searchFields = $this->getSearchFields($this->getMetadata());

        $qb = $this->getListQueryBuilder(10, 1, [], null, []);
        if (!empty($searchQuery)) {
            foreach ($searchFields as $searchField) {
                $qb->orWhere('e.' . $searchField . ' LIKE :translated_title');
            }
            $qb->setParameter('translated_title', '%' . $searchQuery . '%');
        }

        $query = $qb->getQuery();
        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );
        $this->addTranslatableLocaleHint($query, $this->getObjectManager());
        $results = $query->getResult();

        return $this->prepareData($results, $this->getMetadata());
    }

    /**
     * Search entities by primary key
     *
     * @param $keys[]
     *
     * @return array
     */
    public function findValueByPrimaryKey($keys)
    {
        if (empty($keys)) {
            return [];
        }

        $keyField = $this->getEntityIdentifierFieldName($this->getMetadata());

        $qb = $this->getListQueryBuilder(-1, 1, [], null, []);
        $qb->andWhere('e.' . $keyField . ' in (:keys)');
        $qb->setParameter('keys', $keys);

        $query = $qb->getQuery();
        $results = $query->getResult();

        return $this->prepareData($results, $this->getMetadata());
    }

    /**
     * @param object[] $entities
     * @param ClassMetadata $metadata
     *
     * @return array
     */
    protected function prepareData($entities, ClassMetadata $metadata)
    {
        $prepared = [];
        foreach ($entities as $entity) {
            $id = $this->getEntityIdentifier($entity, $metadata);
            $text = $this->entityNameResolver->getName($entity);
            $prepared[] = [
                'id' => $id,
                'value' => $id,
                'text' => $text
            ];
        }

        return $prepared;
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string
     */
    private function getEntityIdentifierFieldName(ClassMetadata $metadata)
    {
        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (count($idFieldNames) === 1) {
            return reset($idFieldNames);
        }

        throw new RuntimeException(
            sprintf('Primary key for entity %s is absent or contains more than one field', $metadata->getName())
        );
    }

    /**
     * @param object $entity
     * @param ClassMetadata $metadata
     *
     * @return mixed
     */
    private function getEntityIdentifier($entity, ClassMetadata $metadata)
    {
        $entityIdentifier = $metadata->getIdentifierValues($entity);
        if (count($entityIdentifier) === 1) {
            return reset($entityIdentifier);
        }

        throw new RuntimeException(
            sprintf('Primary key for entity %s is absent or contains more than one field', $metadata->getName())
        );
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return string[]
     */
    private function getSearchFields(ClassMetadata $metadata)
    {
        $className = $metadata->getName();
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

        throw new \LogicException(
            sprintf('Search fields are not configured for class %s', $metadata->getName())
        );
    }
}
