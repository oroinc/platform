<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;

use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\EntityBundle\Helper\DictionaryHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class DictionaryApiEntityManager extends ApiEntityManager
{
    /** @var ChainDictionaryValueListProvider */
    protected $dictionaryProvider;

    /** @var DictionaryHelper */
    protected $dictionaryHelper;

    /** @var  LocaleSettings */
    protected $localeSettings;

    /**
     * @param ObjectManager $om
     * @param ChainDictionaryValueListProvider $dictionaryProvider
     * @param DictionaryHelper $dictionaryHelper
     */
    public function __construct(
        ObjectManager $om,
        ChainDictionaryValueListProvider $dictionaryProvider,
        DictionaryHelper $dictionaryHelper,
        LocaleSettings $localeSettings
    ) {
        parent::__construct(null, $om);
        $this->dictionaryProvider = $dictionaryProvider;
        $this->dictionaryHelper = $dictionaryHelper;
        $this->localeSettings = $localeSettings;

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
     * Search entities by search string
     *
     * @param $searchQuery
     *
     * @return array
     */
    public function findValueBySearchQuery($searchQuery)
    {
        $locale = $this->localeSettings->getLocale();

        $keyField = $this->dictionaryHelper->getNamePrimaryKeyField($this->getMetadata());
        $labelField = $this->dictionaryHelper->getNameLabelField($this->getMetadata());

        $qb = $this->getListQueryBuilder(-1, 1, [], null, []);
        if (!empty($searchQuery)) {
            $qb->andWhere('e.' . $labelField . ' LIKE :translated_title')
                ->setParameter('translated_title', '%' . $searchQuery . '%');
        }

        $query = $qb->getQuery();
        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
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
        $labelField = $this->dictionaryHelper->getNameLabelField($this->getMetadata());

        $qb = $this->getListQueryBuilder(-1, 1, [], null, []);
        $qb->andWhere('e.' . $keyField . ' in (:keys)')
           ->setParameter('keys', $keys);

        $query = $qb->getQuery();
        $results = $query->getResult();

        return $this->prepareData($results, $keyField, $labelField);
    }

    /**
     * Transform Entity data to array for dictionary filter
     *
     * @param array $results
     * @param string $keyField
     * @param string $labelField
     *
     * @return array
     */
    protected function prepareData($results, $keyField, $labelField)
    {
        $resultD = [];
        $methodGetPK = 'get' . ucfirst($keyField);
        $methodGetLabel = 'get' . ucfirst($labelField);
        foreach ($results as $result) {
            $resultD[] = [
                'id' => $result->$methodGetPK(),
                'value' => $result->$methodGetPK(),
                'text' => $result->$methodGetLabel()
            ];
        }

        return $resultD;
    }
}
