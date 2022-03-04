<?php

namespace Oro\Bundle\TagBundle\Grid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Formatter\ResultFormatter;
use Oro\Bundle\SearchBundle\Query\Result\Item as ResultItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Grid extension that prepares data for tag search results grid
 */
class TagSearchResultsExtension extends AbstractExtension
{
    const TYPE_PATH  = '[columns][entity][type]';
    const TYPE_VALUE = 'tag-search-result';

    protected ResultFormatter $resultFormatter;

    protected ObjectMapper $mapper;

    protected EventDispatcherInterface $dispatcher;

    protected EntityNameResolver $nameResolver;

    public function __construct(
        ResultFormatter $resultFormatter,
        ObjectMapper $mapper,
        EventDispatcherInterface $dispatcher,
        EntityNameResolver $nameResolver
    ) {
        $this->resultFormatter = $resultFormatter;
        $this->mapper = $mapper;
        $this->dispatcher = $dispatcher;
        $this->nameResolver = $nameResolver;
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
                    [],
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
            $item->setSelectedData(['name' => $entity ? $this->nameResolver->getName($entity) : '']);
            $this->dispatcher->dispatch(new PrepareResultItemEvent($item, $entity), PrepareResultItemEvent::EVENT_NAME);
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
