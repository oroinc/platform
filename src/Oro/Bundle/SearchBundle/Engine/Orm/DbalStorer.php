<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Carbon\Carbon;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Entity\Item;

class DbalStorer
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * Oro\Bundle\SearchBundle\Entity\Item data to be stored into db
     *
     * @var array
     * [
     *     spl_object_hash => [
     *         'id'         => id of the item,
               'entity'     => class of the entity,
               'alias'      => alias of the entity,
               'record_id'  => id of the entity,
               'title'      => title of the entity,
               'changed'    => changed attribute,
               'created_at' => when the Item was created,
               'updated_at' => when the Item was updated,
     *     ],
     *     ...
     * ]
     */
    protected $itemData = [];

    /**
     * Doctrine types for columns in $itemData
     *
     * @var array
     * [
     *     'insert' => [
     *         'integer',
     *         ...
     *     ],
     *     'update' => [
     *         'string',
     *         ...
     *     ],
     * ]
     */
    protected $itemTypes = [];

    /**
     * We need this to prevent reuse of object hashs
     *
     * @var Item[]
     */
    protected $items = [];

    /**
     * @var array
     * [
     *     'table_name' => [
     *         'columns' => array of Index column names to insert
     *         'types'   => array of doctrine types of columns to insert for all values
     *         'data' => [
     *             [
     *                 'itemRef' => string reference (object hash) to item so we can retrieve Item::id after insert
     *                 'values'  => values for 'columns' for one record to insert
     *             ],
     *             ...
     *         ],
     *     ],
     *     ...
     * ]
     */
    protected $indexInsertData = [];

    /**
     * @var array
     * [
     *     'table_name' => [
     *         'types' =>  array of doctrine types of columns to insert for one record
     *         'data'  => [
     *             [
     *                 'itemRef' => string reference (object hash) to item so we can retrieve Item::id after insert
     *                 'values'  => values for 'columns' for one record to update
     *             ],
     *             ...
     *         ],
     *     ],
     *     ...
     * ]
     */
    protected $indexUpdateData = [];

    /**
     * Map of types of 'value' column for table
     *
     * @var array
     */
    protected $indexValueTypes = [
        'oro_search_index_integer'  => Type::INTEGER,
        'oro_search_index_text'     => Type::TEXT,
        'oro_search_index_decimal'  => Type::DECIMAL,
        'oro_search_index_datetime' => Type::DATETIME,
    ];

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Stores all data taken from Items given by 'addItem' method
     */
    public function store()
    {
        $connection = $this->getConnection();

        $this->processItems($connection);
        $multiInsertQueryData = [];
        $this->fillQueryData($connection, $multiInsertQueryData);

        $this->runMultiInserts($connection, $multiInsertQueryData);
        $this->runUpdates($connection, $this->indexUpdateData);

        $this->items = [];
        $this->itemData = [];
        $this->indexInsertData = [];
        $this->indexUpdateData = [];
    }

    /**
     * Adds Item of which data will be stored when 'store' method is called
     *
     * @param Item $item
     */
    public function addItem(Item $item)
    {
        $this->populateItem($item);
        $this->populateIndex($item->getIntegerFields(), $item, 'oro_search_index_integer');
        $this->populateIndex($item->getTextFields(), $item, 'oro_search_index_text');
        $this->populateIndex($item->getDecimalFields(), $item, 'oro_search_index_decimal');
        $this->populateIndex($item->getDatetimeFields(), $item, 'oro_search_index_datetime');
    }

    /**
     * Prepares index data for queries to be stored
     *
     * @param Connection $connection
     * @param array $multiInsertQueryData
     */
    protected function fillQueryData(Connection $connection, array &$multiInsertQueryData)
    {
        foreach ($this->indexInsertData as $table => $data) {
            $insertValues = [];
            foreach ($data['data'] as $record) {
                $record['values']['item_id'] = $this->itemData[$record['itemRef']]['id'];
                foreach ($record['values'] as $value) {
                    array_push($insertValues, $value);
                }
            }

            if ($insertValues) {
                $multiInsertQueryData[$table] = [
                    'query' => sprintf(
                        'INSERT INTO %s (%s) VALUES %s',
                        $connection->quoteIdentifier($table),
                        implode(', ', array_map([$connection, 'quoteIdentifier'], $data['columns'])),
                        implode(
                            ', ',
                            array_fill(
                                0,
                                count($data['data']),
                                sprintf('(%s)', implode(', ', array_fill(0, count($data['columns']), '?')))
                            )
                        )
                    ),
                    'values' => $insertValues,
                    'types' => $data['types'],
                ];
            }
        }

        foreach ($this->indexUpdateData as $table => $data) {
            foreach ($data['data'] as $record) {
                $record['values']['item_id'] = $this->itemData[$record['itemRef']]['id'];
            }
        }
    }

    /**
     * Runs multi inserts taken from $multiInsertQueryData argument
     *
     * @param Connection $connection
     * @param array $multiInsertQueryData
     */
    protected function runMultiInserts(Connection $connection, array $multiInsertQueryData)
    {
        foreach ($multiInsertQueryData as $data) {
            $connection->executeQuery($data['query'], $data['values'], $data['types']);
        }
    }

    /**
     * Runs updates taken from $updateQueryData argument
     *
     * @param Connection $connection
     * @param array $updateQueryData
     */
    protected function runUpdates(Connection $connection, array $updateQueryData)
    {
        foreach ($updateQueryData as $table => $data) {
            foreach ($data['data'] as $record) {
                $connection->update(
                    $connection->quoteIdentifier($table),
                    $record['values'],
                    ['id' => $record['values']['id']],
                    $data['types']
                );
            }
        }
    }

    /**
     * Stores items from $this->itemData and updates their ids
     *
     * @param Connection $connection
     */
    protected function processItems(Connection $connection)
    {
        $now = Carbon::now();
        $table = $connection->quoteIdentifier('oro_search_item');
        foreach ($this->itemData as &$data) {
            $data['updated_at'] = $now;
            if (isset($data['id'])) {
                $connection->update(
                    $table,
                    $data,
                    ['id' => $data['id']],
                    $this->itemTypes['update']
                );
            } else {
                $data['created_at'] = $now;
                $connection->insert(
                    $table,
                    $data,
                    $this->itemTypes['insert']
                );
                $data['id'] = $connection->lastInsertId(
                    $connection->getDatabasePlatform() instanceof PostgreSqlPlatform ? 'oro_search_item_id_seq' : null
                );
            }
        }
    }

    /**
     * Converts $item into array and stores the result in the DbalStorer object
     *
     * @param Item $item
     */
    protected function populateItem(Item $item)
    {
        $this->items[spl_object_hash($item)] = $item;

        if (!$this->itemTypes) {
            $this->itemTypes = [
                'insert' => [
                    Type::STRING,
                    Type::STRING,
                    Type::INTEGER,
                    Type::STRING,
                    Type::BOOLEAN,
                    Type::DATETIME,
                    Type::DATETIME,
                ],
                'update' => [
                    Type::INTEGER,
                    Type::STRING,
                    Type::STRING,
                    Type::INTEGER,
                    Type::STRING,
                    Type::BOOLEAN,
                    Type::DATETIME,
                    Type::DATETIME,
                ],
            ];
        }

        if ($item->getId()) {
            $this->itemData[spl_object_hash($item)] = [
                'id' => $item->getId(),
                'entity' => $item->getEntity(),
                'alias' => $item->getAlias(),
                'record_id' => $item->getRecordId(),
                'title' => $item->getTitle(),
                'changed' => $item->getChanged(),
                'created_at' => $item->getCreatedAt(),
                'updated_at' => $item->getUpdatedAt(),
            ];
        } else {
            $this->itemData[spl_object_hash($item)] = [
                'entity' => $item->getEntity(),
                'alias' => $item->getAlias(),
                'record_id' => $item->getRecordId(),
                'title' => $item->getTitle(),
                'changed' => $item->getChanged(),
                'created_at' => $item->getCreatedAt(),
                'updated_at' => $item->getUpdatedAt(),
            ];
        }
    }

    /**
     * Converts indexes of $item into objects and stores them in the DbalStorer object
     *
     * @param Collection $fields
     * @param Item $item
     * @param string $table
     */
    protected function populateIndex(Collection $fields, Item $item, $table)
    {
        if ($fields->isEmpty()) {
            return;
        }

        if (!isset($this->indexUpdateData[$table])) {
            $this->indexUpdateData[$table] = [
                'data'  => [],
                'types' => [
                    Type::INTEGER,
                    Type::STRING,
                    $this->indexValueTypes[$table],
                    Type::INTEGER,
                ],
            ];
            $this->indexInsertData[$table] = [
                'columns' => ['field', 'value', 'item_id'],
                'data'  => [],
                'types' => [],
            ];
        }

        foreach ($fields as $field) {
            if ($field->getId()) {
                $this->indexUpdateData[$table]['data'][] = [
                    'itemRef' => spl_object_hash($item),
                    'values' => [
                        'id' => $field->getId(),
                        'field' => $field->getField(),
                        'value' => $field->getValue(),
                        'item_id' => $item->getId(),
                    ],
                ];
            } else {
                $this->indexInsertData[$table]['data'][] = [
                    'itemRef' => spl_object_hash($item),
                    'values' => [
                        'field' => $field->getField(),
                        'value' => $field->getValue(),
                        'item_id' => $item->getId(),
                    ],
                ];

                array_push(
                    $this->indexInsertData[$table]['types'],
                    Type::STRING,
                    $this->indexValueTypes[$table],
                    Type::INTEGER
                );
            }
        }
        $fields->clear();
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->doctrineHelper->getEntityManager('OroSearchBundle:Item')
            ->getConnection();
    }
}
