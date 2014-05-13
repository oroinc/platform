<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class ResultStatisticsProvider
{
    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param Indexer       $indexer
     * @param ConfigManager $configManager
     * @param Translator $translator
     */
    public function __construct(Indexer $indexer, ConfigManager $configManager, Translator $translator)
    {
        $this->indexer = $indexer;
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     *
     * @param $query
     * @return \Oro\Bundle\SearchBundle\Query\Result
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
        $search = $this->getResults($string);

        // empty key array contains all data
        $result = array(
            '' => array(
                'count'  => 0,
                'class'  => '',
                'config' => array()
            )
        );

        /** @var $item Item */
        foreach ($search->getElements() as $item) {
            $config = $item->getEntityConfig();
            $alias  = $config['alias'];

            if (!isset($result[$alias])) {
                $result[$alias] = array(
                    'count'  => 0,
                    'class'  => $item->getEntityName(),
                    'config' => $config,
                );
            }

            $result[$alias]['count']++;
            $result['']['count']++;
        }

        return $this->sortResultGroups($result);
    }

    protected function sortResultGroups(array $results)
    {
        foreach ($results as &$result) {
            $result['label'] = '';
            $result['icon'] = '';
            if (!empty($result['class']) && $this->configManager->hasConfig($result['class'])) {
                $entityConfigId = new EntityConfigId('entity', $result['class']);
                $entityConfig = $this->configManager->getConfig($entityConfigId);
                if ($entityConfig->has('plural_label')) {
                    $result['label'] = $this->translator->trans($entityConfig->get('plural_label'));
                }
                if ($entityConfig->has('icon')) {
                    $result['icon'] = $entityConfig->get('icon');
                }
            }
        }

        uasort(
            $results,
            function ($first, $second) {
                if ($first['label'] == $second['label']) {
                    return 0;
                }

                return $first['label'] > $second['label'] ? 1 : -1;
            }
        );

        return $results;
    }
}
