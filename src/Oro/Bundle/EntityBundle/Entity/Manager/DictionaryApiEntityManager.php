<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\Helper\DictionaryHelper;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class DictionaryApiEntityManager extends ApiEntityManager
{
    /** @var ChainDictionaryValueListProvider */
    protected $dictionaryProvider;

    /** @var DictionaryHelper */
    protected $dictionaryHelper;

    /**
     * @var ConfigManager
     */
    protected $entityConfigManager;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param ObjectManager $om
     * @param ChainDictionaryValueListProvider $dictionaryProvider
     * @param DictionaryHelper $dictionaryHelper
     * @param ConfigManager $entityConfigManager
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        ObjectManager $om,
        ChainDictionaryValueListProvider $dictionaryProvider,
        DictionaryHelper $dictionaryHelper,
        ConfigManager $entityConfigManager,
        PropertyAccessor $propertyAccessor
    ) {
        parent::__construct(null, $om);
        $this->dictionaryProvider = $dictionaryProvider;
        $this->dictionaryHelper = $dictionaryHelper;
        $this->entityConfigManager = $entityConfigManager;
        $this->propertyAccessor = $propertyAccessor;
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

    /**
     * Search entities by search query
     *
     * @param $searchQuery
     *
     * @return array
     */
    public function findValueBySearchQuery($searchQuery)
    {
        $entityMetadata = $this->entityConfigManager->getEntityMetadata($this->class);

        $keyField = $this->dictionaryHelper->getNamePrimaryKeyField($this->getMetadata());
        $searchFields = $this->dictionaryHelper->getSearchFields($this->getMetadata(), $entityMetadata);
        $representationField = $this->dictionaryHelper->getRepresentationField($this->getMetadata(), $entityMetadata);

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
        $results = $query->getResult();

        return $this->prepareData($results, $keyField, $searchFields, $representationField);
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
        $entityMetadata = $this->entityConfigManager->getEntityMetadata($this->class);

        $keyField = $this->dictionaryHelper->getNamePrimaryKeyField($this->getMetadata());
        $searchFields = $this->dictionaryHelper->getSearchFields($this->getMetadata(), $entityMetadata);
        $representationField = $this->dictionaryHelper->getRepresentationField($this->getMetadata(), $entityMetadata);

        $qb = $this->getListQueryBuilder(-1, 1, [], null, []);
        $qb->andWhere('e.' . $keyField . ' in (:keys)');
        $qb->setParameter('keys', $keys);

        $query = $qb->getQuery();
        $results = $query->getResult();

        return $this->prepareData($results, $keyField, $searchFields, $representationField);
    }

    /**
     * @param array $results
     * @param string $keyField
     * @param array $searchFields
     * @param string $representationField
     *
     * @return array
     */
    protected function prepareData($results, $keyField, $searchFields, $representationField)
    {
        $prepared = [];
        foreach ($results as $result) {
            $id = $value = $this->propertyAccessor->getValue($result, $keyField);
            if ($representationField !== null) {
                $text = $this->propertyAccessor->getValue($result, $representationField);
            } else {
                $text = implode(' ', array_map(function ($field) use ($result) {
                    return $this->propertyAccessor->getValue($result, $field);
                }, $searchFields));
            }

            $prepared[] = [
                'id' => $id,
                'value' => $value,
                'text' => $text,
            ];
        }

        return $prepared;
    }
}
