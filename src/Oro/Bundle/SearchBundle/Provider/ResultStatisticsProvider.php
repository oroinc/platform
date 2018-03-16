<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Indexer             $indexer
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     */
    public function __construct(Indexer $indexer, ConfigManager $configManager, TranslatorInterface $translator)
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
                'config' => array(),
                'icon'   => '',
                'label'  => ''
            )
        );

        /** @var $item Item */
        foreach ($search->getElements() as $item) {
            $config = $item->getEntityConfig();
            $alias  = $config['alias'];

            if (!isset($result[$alias])) {
                $group = array(
                    'count'  => 0,
                    'class'  => $item->getEntityName(),
                    'config' => $config,
                    'icon'   => '',
                    'label'  => ''
                );

                if (!empty($group['class']) && $this->configManager->hasConfig($group['class'])) {
                    $entityConfigId = new EntityConfigId('entity', $group['class']);
                    $entityConfig = $this->configManager->getConfig($entityConfigId);
                    if ($entityConfig->has('plural_label')) {
                        $group['label'] = $this->translator->trans($entityConfig->get('plural_label'));
                    }
                    if ($entityConfig->has('icon')) {
                        $group['icon'] = $entityConfig->get('icon');
                    }
                }

                $result[$alias] = $group;
            }

            $result[$alias]['count']++;
            $result['']['count']++;
        }

        uasort(
            $result,
            function ($first, $second) {
                if ($first['label'] == $second['label']) {
                    return 0;
                }

                return $first['label'] > $second['label'] ? 1 : -1;
            }
        );

        return $result;
    }
}
