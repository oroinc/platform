<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a set of methods to search data.
 */
class SearchResultProvider
{
    private const EMPTY_RESULT_ROW = [
        'count' => 0,
        'class' => '',
        'icon' => '',
        'label' => ''
    ];

    private Indexer $indexer;
    private FeatureChecker $featureChecker;
    private ConfigManager $configManager;
    private EventDispatcherInterface $dispatcher;
    private TranslatorInterface $translator;

    public function __construct(
        Indexer $indexer,
        FeatureChecker $featureChecker,
        ConfigManager $configManager,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator
    ) {
        $this->indexer = $indexer;
        $this->featureChecker = $featureChecker;
        $this->configManager = $configManager;
        $this->dispatcher = $dispatcher;
        $this->translator = $translator;
    }

    /**
     * @return array [entity class => entity alias, ...]
     */
    public function getAllowedEntities(): array
    {
        $entities = $this->indexer->getAllowedEntitiesListAliases();
        foreach (array_keys($entities) as $entityClass) {
            if (!$this->featureChecker->isResourceEnabled($entityClass, 'entities')) {
                unset($entities[$entityClass]);
            }
        }

        return $entities;
    }

    public function getSuggestions(
        string $searchString,
        ?string $from = null,
        ?int $offset = null,
        ?int $maxResults = null
    ): array {
        $searchResults = $this->indexer->simpleSearch(
            $searchString,
            $offset ?? 0,
            $maxResults ?? 0,
            $from ?: array_values($this->getAllowedEntities())
        );

        $items = $searchResults->getElements();
        foreach ($items as $item) {
            $this->dispatcher->dispatch(new PrepareResultItemEvent($item), PrepareResultItemEvent::EVENT_NAME);
        }

        return $searchResults->toSearchResultData();
    }

    /**
     * @return array [entity alias => ['count' => int, 'class' => string, 'icon' => string, 'label' => string], ...]
     */
    public function getGroupedResultsBySearchQuery(string $searchString): array
    {
        $docsCountByEntity = $this->indexer->getDocumentsCountGroupByEntityFQCN(
            $searchString,
            array_values($this->getAllowedEntities())
        );

        // empty key array contains all data
        $result = [
            '' => self::EMPTY_RESULT_ROW
        ];

        $totalItemsCount = 0;
        foreach ($docsCountByEntity as $entityClass => $documentCount) {
            $alias = $this->indexer->getEntityAlias($entityClass);
            $group = array_merge(self::EMPTY_RESULT_ROW, [
                'count' => (int)$documentCount,
                'class' => $entityClass
            ]);

            $this->addEntityInformation($entityClass, $group);
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
            $group['label'] = $this->translator->trans((string) $entityConfig->get('plural_label'));
        }
        if ($entityConfig->has('icon')) {
            $group['icon'] = $entityConfig->get('icon');
        }
    }

    private function sortResult(array &$result): void
    {
        uasort(
            $result,
            static function ($first, $second) {
                return $first['label'] <=> $second['label'];
            }
        );
    }
}
