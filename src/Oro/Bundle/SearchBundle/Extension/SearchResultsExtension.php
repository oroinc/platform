<?php

namespace Oro\Bundle\SearchBundle\Extension;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Formatter\ResultFormatter;
use Oro\Bundle\SearchBundle\Query\Result\Item as ResultItem;

class SearchResultsExtension extends AbstractExtension
{
    const TYPE_PATH  = '[columns][entity][type]';
    const TYPE_VALUE = 'search-result';

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
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(self::TYPE_PATH) == self::TYPE_VALUE ? true : false;
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $rows = $result->getData();
        $rows = is_array($rows) ? $rows : [];

        $rows = array_map(
            function (ResultRecordInterface $record) {
                if ($rootEntity = $record->getRootEntity()) {
                    return $rootEntity;
                }

                $entityName = $record->getValue('entityName');
                $recordId   = $record->getValue('recordId');
                if ($entityName && $recordId) {
                    return new ResultItem(
                        $this->em,
                        $entityName,
                        $recordId,
                        null,
                        null,
                        null,
                        $this->mapper->getEntityConfig($entityName)
                    );
                }

                return null;
            },
            $rows
        );

        $entities = $this->resultFormatter->getResultEntities($rows);

        $resultRows = [];
        /** @var ResultItem $item */
        foreach ($rows as $item) {
            $entityName = $item->getEntityName();
            $entityId   = $item->getRecordId();
            if (!isset($entities[$entityName][$entityId])) {
                continue;
            }

            $entity = $entities[$entityName][$entityId];

            $this->dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item, $entity));

            $resultRows[] = new ResultRecord(['entity' => $entity, 'indexer_item' => $item]);
        }

        // set results
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
