<?php

namespace Oro\Bundle\TagBundle\Grid\Extension;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Formatter\ResultFormatter;
use Oro\Bundle\SearchBundle\Query\Result\Item as ResultItem;

class TagSearchResultsExtension extends AbstractExtension
{
    const TYPE_PATH  = '[columns][entity][type]';
    const TYPE_VALUE = 'tag-search-result';

    /** @var ResultFormatter */
    protected $resultFormatter;

    /** @var EntityManager */
    protected $em;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param ResultFormatter          $formatter
     * @param EntityManager            $em
     * @param ObjectMapper             $mapper
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ResultFormatter $formatter,
        EntityManager $em,
        ObjectMapper $mapper,
        EventDispatcherInterface $dispatcher
    ) {
        $this->resultFormatter = $formatter;
        $this->em              = $em;
        $this->mapper          = $mapper;
        $this->dispatcher      = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(self::TYPE_PATH) === self::TYPE_VALUE;
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
                    $this->em,
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
