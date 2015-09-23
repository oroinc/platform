<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

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

    protected $entityConfigManager;

    /**
     * @param ObjectManager $om
     * @param ChainDictionaryValueListProvider $dictionaryProvider
     * @param DictionaryHelper $dictionaryHelper
     * @param ConfigManager $entityConfigManager
     */
    public function __construct(
        ObjectManager $om,
        ChainDictionaryValueListProvider $dictionaryProvider,
        DictionaryHelper $dictionaryHelper,
        ConfigManager $entityConfigManager
    ) {
        parent::__construct(null, $om);
        $this->dictionaryProvider = $dictionaryProvider;
        $this->dictionaryHelper = $dictionaryHelper;
        $this->entityConfigManager = $entityConfigManager;
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
        $keyField = $this->dictionaryHelper->getNamePrimaryKeyField($this->getMetadata());
        $labelField = $this->dictionaryHelper->getNameLabelField(
            $this->getMetadata(),
            $this->entityConfigManager->getEntityMetadata($this->class)
        );

        $qb = $this->getListQueryBuilder(10, 1, [], null, []);
        if (!empty($searchQuery)) {
            $qb->andWhere('e.' . $labelField . ' LIKE :translated_title')
                ->setParameter('translated_title', '%' . $searchQuery . '%');
        }

        $query = $qb->getQuery();
        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );
        $results = $query->getResult();

        return $this->prepareData($results, $keyField, $labelField);
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
        $keyField = $this->dictionaryHelper->getNamePrimaryKeyField($this->getMetadata());
        $labelField = $this->dictionaryHelper->getNameLabelField(
            $this->getMetadata(),
            $this->entityConfigManager->getEntityMetadata($this->class)
        );

        $qb = $this->getListQueryBuilder(-1, 1, [], null, []);
        $qb->andWhere('e.' . $keyField . ' in (:keys)')
           ->setParameter('keys', $keys);

        $query = $qb->getQuery();
        $results = $query->getResult();

        return $this->prepareData($results, $keyField, $labelField);
    }

    /**
     * Transform Entity data to array
     *
     * @param array $results
     * @param string $keyField
     * @param string $labelField
     *
     * @return array
     */
    protected function prepareData($results, $keyField, $labelField)
    {
        $resultsData = [];
        $methodGetPK = 'get' . ucfirst($keyField);
        $methodGetLabel = 'get' . ucfirst($labelField);
        foreach ($results as $result) {
            $resultsData[] = [
                'id' => $result->$methodGetPK(),
                'value' => $result->$methodGetPK(),
                'text' => $result->$methodGetLabel()
            ];
        }

        return $resultsData;
    }
}
