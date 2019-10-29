<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Exception\UnsupportedStatisticInterfaceEngineException;
use Oro\Bundle\SearchBundle\Query\Result;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Contains methods that provides statistical data
 */
class ResultStatisticsProvider
{
    private const EMPTY_RESULT_ROW = [
        'count' => 0,
        'class' => '',
        'config' => [],
        'icon' => '',
        'label' => ''
    ];

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ObjectMapper
     */
    private $mapper;

    /**
     * @param Indexer $indexer
     * @param ConfigManager $configManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Indexer $indexer,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->indexer = $indexer;
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     * @param ObjectMapper $mapper
     */
    public function setMapper(ObjectMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     *
     * @param $query
     * @return Result
     */
    public function getResults($query)
    {
        return $this->indexer->simpleSearch($query);
    }

    /**
     * Returns grouped search results
     *
     * @param string $string
     * @return array
     */
    public function getGroupedResults($string)
    {
        try {
            $docsCountByEntity = $this->indexer->getDocumentsCountGroupByEntityFQCN($string);
        } catch (UnsupportedStatisticInterfaceEngineException $exception) {
            return $this->getGroupedResultsOld($string);
        }

        // empty key array contains all data
        $result = [
            '' => self::EMPTY_RESULT_ROW
        ];

        $totalItemsCount = 0;
        foreach ($docsCountByEntity as $entityFQCN => $documentCount) {
            $group = array_merge(self::EMPTY_RESULT_ROW, [
                'count' => (int)$documentCount,
                'class' => $entityFQCN,
                'config' => $this->mapper->getEntityConfig($entityFQCN),
            ]);
            $this->addEntityInformation($entityFQCN, $group);

            $alias = $this->indexer->getEntityAlias($entityFQCN);
            $result[$alias] = $group;

            $totalItemsCount += (int)$documentCount;
        }
        $result['']['count'] += $totalItemsCount;
        $this->sortResult($result);

        return $result;
    }

    /**
     * @param string $string
     * @return array
     */
    private function getGroupedResultsOld($string)
    {
        $search = $this->getResults($string);

        // empty key array contains all data
        $result = [
            '' => self::EMPTY_RESULT_ROW
        ];

        /** @var Result\Item $item */
        foreach ($search->getElements() as $item) {
            $config = $item->getEntityConfig();
            $alias  = $config['alias'];

            if (!isset($result[$alias])) {
                $group = array_merge(self::EMPTY_RESULT_ROW, [
                    'class' => $item->getEntityName(),
                    'config' => $config,
                ]);

                if (!empty($group['class'])) {
                    $this->addEntityInformation($group['class'], $group);
                }

                $result[$alias] = $group;
            }

            $result[$alias]['count']++;
            $result['']['count']++;
        }
        $this->sortResult($result);

        return $result;
    }

    /**
     * @param string $className
     * @param array $group
     */
    private function addEntityInformation(string $className, array &$group): void
    {
        if (!$this->configManager->hasConfig($className)) {
            return;
        }

        $entityConfigId = new EntityConfigId('entity', $className);
        $entityConfig = $this->configManager->getConfig($entityConfigId);
        if ($entityConfig->has('plural_label')) {
            $group['label'] = $this->translator->trans($entityConfig->get('plural_label'));
        }

        if ($entityConfig->has('icon')) {
            $group['icon'] = $entityConfig->get('icon');
        }
    }

    /**
     * @param array $result
     */
    private function sortResult(array &$result)
    {
        uasort(
            $result,
            function ($first, $second) {
                return $first['label'] <=> $second['label'];
            }
        );
    }
}
