<?php

namespace Oro\Bundle\TagBundle\Grid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Formatter\ResultFormatter;
use Oro\Bundle\SearchBundle\Query\Result\Item as ResultItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TagSearchResultsExtension extends AbstractExtension
{
    const TYPE_PATH  = '[columns][entity][type]';
    const TYPE_VALUE = 'tag-search-result';

    /** @var ResultFormatter */
    protected $resultFormatter;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param ResultFormatter          $formatter
     * @param ObjectMapper             $mapper
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ResultFormatter $formatter,
        ObjectMapper $mapper,
        EventDispatcherInterface $dispatcher
    ) {
        $this->resultFormatter = $formatter;
        $this->mapper          = $mapper;
        $this->dispatcher      = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->offsetGetByPath(self::TYPE_PATH) === self::TYPE_VALUE;
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $rows = $result->getData();

        $mappingConfig = $this->mapper->getMappingConfig();

        $rows = array_map(
            function (ResultRecordInterface $record) use ($mappingConfig) {
                $entityClass = $record->getValue('entityName');
                $entityId    = $record->getValue('recordId');

                $entityConfig = array_key_exists($entityClass, $mappingConfig)
                    ? $entityConfig = $this->mapper->getEntityConfig($entityClass)
                    : [];

                return new ResultItem(
                    $entityClass,
                    $entityId,
                    null,
                    null,
                    $entityConfig
                );
            },
            $rows
        );

        $entities = $this->resultFormatter->getResultEntities($rows);

        $resultRows = [];
        /** @var ResultItem $item */
        foreach ($rows as $item) {
            $entityClass = $item->getEntityName();
            $entityId    = $item->getRecordId();
            $entity      = $entities[$entityClass][$entityId];
            $this->dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item, $entity));
            $resultRows[] = new ResultRecord(['entity' => $entity, 'indexer_item' => $item]);
        }

        $result->setData($resultRows);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }
}
