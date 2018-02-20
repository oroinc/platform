<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Carbon\Carbon;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\SearchBundle\Entity\AbstractItem;

/**
 * @property EntityManagerInterface $entityManager
 * @deprecated sience 1.5. Please use the BaseDriver.
 */
trait DBALPersisterDriverTrait
{
    /**
     * Oro\Bundle\SearchBundle\Entity\AbstractItem data to be stored into db
     *
     * @var array
     * [
     *     spl_object_hash => [
     *         'id'         => id of the item,
     *         'entity'     => class of the entity,
     *         'alias'      => alias of the entity,
     *         'record_id'  => id of the entity,
     *         'title'      => title of the entity,
     *         'changed'    => changed attribute,
     *         'created_at' => when the Item was created,
     *         'updated_at' => when the Item was updated,
     *     ],
     *     ...
     * ]
     */
    private $writeableItemData = [];

    /**
     * Doctrine types for columns in $writeableItemData
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
    private $writeableItemTypes = [];

    /**
     * We need this to prevent reuse of object hashs
     *
     * @var AbstractItem[]
     */
    private $writeableItems = [];

    /**
     * @var array
     * [
     *     'table_name' => [
     *         'columns' => array of Index column names to insert
     *         'types'   => array of doctrine types of columns to insert for all values
     *         'data' => [
     *             [
     *                 'itemRef' => string reference (object hash) to item
     *                              so we can retrieve AbstractItem::id after insert
     *                 'values'  => values for 'columns' for one record to insert
     *             ],
     *             ...
     *         ],
     *     ],
     *     ...
     * ]
     */
    private $indexInsertData = [];

    /**
     * @var array
     * [
     *     'table_name' => [
     *         'types' =>  array of doctrine types of columns to insert for one record
     *         'data'  => [
     *             [
     *                 'itemRef' => string reference (object hash) to item
     *                              so we can retrieve AbstractItem::id after insert
     *                 'values'  => values for 'columns' for one record to update
     *             ],
     *             ...
     *         ],
     *     ],
     *     ...
     * ]
     */
    private $indexUpdateData = [];

    /**
     * Stores all data taken from Items given by 'writeItem' method
     */
    public function flushWrites()
    {
        $connection = $this->getConnection();

        $this->processItems($connection);
        $multiInsertQueryData = [];
        $this->fillQueryData($connection, $multiInsertQueryData);

        $this->runMultiInserts($connection, $multiInsertQueryData);
        $this->runUpdates($connection, $this->indexUpdateData);

        $this->writeableItems = [];
        $this->writeableItemData = [];
        $this->indexInsertData = [];
        $this->indexUpdateData = [];
    }

    /**
     * Adds AbstractItem of which data will be stored when 'flushWrites' method is called
     *
     * @param AbstractItem $item
     */
    public function writeItem(AbstractItem $item)
    {
        $this->populateItem($item);
        $this->populateIndexByType($item->getIntegerFields(), $item, 'integer');
        $this->populateIndexByType($item->getTextFields(), $item, 'text');
        $this->populateIndexByType($item->getDecimalFields(), $item, 'decimal');
        $this->populateIndexByType($item->getDatetimeFields(), $item, 'datetime');
    }

    /**
     * Prepares index data for queries to be stored
     *
     * @param Connection $connection
     * @param array $multiInsertQueryData
     */
    private function fillQueryData(Connection $connection, array &$multiInsertQueryData)
    {
        foreach ($this->indexInsertData as $table => $data) {
            $insertValues = [];
            foreach ($data['data'] as $record) {
                $record['values']['item_id'] = $this->writeableItemData[$record['itemRef']]['id'];
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
                $record['values']['item_id'] = $this->writeableItemData[$record['itemRef']]['id'];
            }
        }
    }

    /**
     * Runs multi inserts taken from $multiInsertQueryData argument
     *
     * @param Connection $connection
     * @param array $multiInsertQueryData
     * @return Statement[]
     * @throws \Doctrine\DBAL\DBALException
     */
    private function runMultiInserts(Connection $connection, array $multiInsertQueryData)
    {
        $result = [];
        foreach ($multiInsertQueryData as $key => $data) {
            $result[$key] = $connection->executeQuery($data['query'], $data['values'], $data['types']);
        }
        return $result;
    }

    /**
     * Runs updates taken from $updateQueryData argument
     *
     * @param Connection $connection
     * @param array $updateQueryData
     */
    private function runUpdates(Connection $connection, array $updateQueryData)
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
     * Stores items from $this->writeableItemData and updates their ids
     *
     * @param Connection $connection
     */
    private function processItems(Connection $connection)
    {
        $now = Carbon::now();

        if (empty($this->writeableItems)) {
            return;
        }

        $tablePlain = $this->getEntityTable(current($this->writeableItems));
        $table = $connection->quoteIdentifier($tablePlain);

        foreach ($this->writeableItemData as &$data) {
            $data['updated_at'] = $now;
            if (isset($data['id'])) {
                $connection->update(
                    $table,
                    $data,
                    ['id' => $data['id']],
                    $this->writeableItemTypes['update']
                );
            } else {
                $data['created_at'] = $now;
                $connection->insert(
                    $table,
                    $data,
                    $this->writeableItemTypes['insert']
                );
                $data['id'] = $connection->lastInsertId(
                    $this->getSequenceName($tablePlain, $connection)
                );
            }
        }
    }

    /**
     * Converts $item into array and stores the result in the object
     *
     * @param AbstractItem $item
     */
    private function populateItem(AbstractItem $item)
    {
        $this->writeableItems[spl_object_hash($item)] = $item;

        if (!$this->writeableItemTypes) {
            $this->writeableItemTypes = [
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
            $this->writeableItemData[spl_object_hash($item)] = [
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
            $this->writeableItemData[spl_object_hash($item)] = [
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
     * Converts indexes of $item into objects and stores them in the object
     *
     * @param Collection   $fields
     * @param AbstractItem $item
     * @param string       $type
     */
    private function populateIndexByType(Collection $fields, AbstractItem $item, $type)
    {
        if ($fields->isEmpty()) {
            return;
        }

        $table = $this->getIndexTable($item, $type);

        if (!isset($this->indexUpdateData[$table])) {
            $this->indexUpdateData[$table] = [
                'data'  => [],
                'types' => [
                    Type::INTEGER,
                    Type::STRING,
                    $type,
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
                    $type,
                    Type::INTEGER
                );
            }
        }
        $fields->clear();
    }

    /**
     * @return Connection
     */
    private function getConnection()
    {
        return $this->entityManager
            ->getConnection();
    }

    /**
     * @param AbstractItem $item
     * @return string
     */
    private function getEntityTable(AbstractItem $item)
    {
        return $this->entityManager
            ->getClassMetadata(get_class($item))
            ->getTableName();
    }

    /**
     * @param AbstractItem $item
     * @param string       $type
     * @return string
     */
    private function getIndexTable($item, $type)
    {
        $tableName = $this->getEntityTable($item);

        $parts = explode('_', $tableName);

        array_pop($parts);

        // hack for classic search index tables
        if ($parts[1] === 'search') {
            $parts[] = 'index';
        }

        $parts[] = $type;

        return implode('_', $parts);
    }

    /**
     * @param string     $entityTable
     * @param Connection $connection
     * @return null|string
     */
    private function getSequenceName($entityTable, Connection $connection)
    {
        if (!$connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            return null;
        }

        $parts = explode('_', $entityTable);

        $parts[] = 'id';
        $parts[] = 'seq';

        return implode('_', $parts);
    }
}
