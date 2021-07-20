<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contains methods that provides statistical data
 */
class ResultStatisticsProvider
{
    private const EMPTY_RESULT_ROW = [
        'count' => 0,
        'class' => '',
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
     * Returns grouped search results
     *
     * @param string $string
     * @return array
     */
    public function getGroupedResultsBySearchQuery($string): array
    {
        $docsCountByEntity = $this->indexer->getDocumentsCountGroupByEntityFQCN($string);

        // empty key array contains all data
        $result = [
            '' => self::EMPTY_RESULT_ROW
        ];

        $totalItemsCount = 0;
        foreach ($docsCountByEntity as $entityFQCN => $documentCount) {
            $alias = $this->indexer->getEntityAlias($entityFQCN);
            $group = array_merge(self::EMPTY_RESULT_ROW, [
                'count' => (int)$documentCount,
                'class' => $entityFQCN
            ]);

            $this->addEntityInformation($entityFQCN, $group);
            $result[$alias] = $group;

            $totalItemsCount += (int)$documentCount;
        }
        $result['']['count'] += $totalItemsCount;
        $this->sortResult($result);

        return $result;
    }

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

    private function sortResult(array &$result)
    {
        uasort(
            $result,
            static function ($first, $second) {
                return $first['label'] <=> $second['label'];
            }
        );
    }
}
