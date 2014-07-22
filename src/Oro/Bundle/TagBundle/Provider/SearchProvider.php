<?php

namespace Oro\Bundle\TagBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Provider\ResultStatisticsProvider;
use Oro\Bundle\TagBundle\Security\SecurityProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class SearchProvider extends ResultStatisticsProvider
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Oro\Bundle\SearchBundle\Engine\ObjectMapper
     */
    protected $mapper;

    /**
     * @var \Oro\Bundle\TagBundle\Security\SecurityProvider
     */
    protected $securityProvider;

    /**
     * @param EntityManager    $em
     * @param ObjectMapper     $mapper
     * @param SecurityProvider $securityProvider
     * @param Indexer          $indexer
     * @param ConfigManager    $configManager
     * @param Translator       $translator
     */
    public function __construct(
        EntityManager $em,
        ObjectMapper $mapper,
        SecurityProvider $securityProvider,
        Indexer $indexer,
        ConfigManager $configManager,
        Translator $translator
    ) {
        $this->em = $em;
        $this->mapper = $mapper;
        $this->securityProvider = $securityProvider;
        parent::__construct($indexer, $configManager, $translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getResults($tagId)
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('t')
            ->from('Oro\Bundle\TagBundle\Entity\Tagging', 't')
            ->where('t.tag = :tag')
            ->setParameter('tag', $tagId)
            ->addGroupBy('t.entityName')
            ->addGroupBy('t.recordId')
            ->addGroupBy('t.id');
        $this->securityProvider->applyAcl($queryBuilder, 't');

        $originResults = $queryBuilder->getQuery()
            ->getResult();

        $results = array();
        /** @var Tagging $item */
        foreach ($originResults as $item) {
            $results[] = new Item(
                $this->em,
                $item->getEntityName(),
                $item->getRecordId(),
                null,
                null,
                null,
                $this->mapper->getEntityConfig($item->getEntityName())
            );
        }

        return new Result(new Query(), $results, count($results));
    }
}
