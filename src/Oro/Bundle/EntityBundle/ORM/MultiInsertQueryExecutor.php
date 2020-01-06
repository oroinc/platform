<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;

/**
 * This executor should be used only for queries that will reduce result count after each execution by itself
 * Be aware that BufferedQueryResultIterator won't work correct for such queries, because it uses SKIP, LIMIT operators
 *
 * Additionally note that it's recommended to use this executor instead of 'insert from select' in mysql, because of
 * innodb auto increment table lock that force to wait to insert request until another insert on the same table be done.
 */
class MultiInsertQueryExecutor implements InsertQueryExecutorInterface
{
    const BUFFER_SIZE = 5000;

    /**
     * @var array
     */
    protected $tablesColumns;

    /**
     * @var NativeQueryExecutorHelper
     */
    protected $helper;

    /**
     * @param NativeQueryExecutorHelper $helper
     */
    public function __construct(NativeQueryExecutorHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     *{@inheritDoc}
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        $columns = $this->getColumns($className, $fields);
        $columnNamesWithNonNamedParameters = array_combine(
            $columns,
            array_fill(0, count($columns), '?')
        );

        $connection = $this->helper->getManager($className)->getConnection();
        $insertToTableName = $this->helper->getTableName($className);
        $columnTypes = $this->getColumnTypes($className, $fields);

        $insert = $connection->createQueryBuilder()
            ->insert($insertToTableName)
            ->values($columnNamesWithNonNamedParameters);

        $total = 0;
        $iterator = new BufferedIdentityQueryResultIterator($this->getQuery($selectQueryBuilder));
        $iterator->setBufferSize(static::BUFFER_SIZE);
        foreach ($iterator as $row) {
            $insert->setParameters(
                array_values($row),
                $columnTypes
            );
            $insert->execute();
            $total++;
        }

        return $total;
    }

    /**
     * @param string $className
     * @param array $fields
     * @return array
     */
    protected function getColumnTypes($className, array $fields): array
    {
        $types = [];
        $classMetadata = $this->helper->getClassMetadata($className);
        foreach ($fields as $field) {
            if (!$classMetadata->hasField($field) && !$classMetadata->hasAssociation($field)) {
                throw new \InvalidArgumentException(sprintf('Field %s is not known for %s', $field, $className));
            }

            if ($classMetadata->hasField($field)) {
                $type = $classMetadata->getTypeOfField($field);
            } else {
                $association = $classMetadata->getAssociationMapping($field);
                $targetMetadata = $this->helper->getClassMetadata($association['targetEntity']);
                $type = $targetMetadata->getTypeOfField(
                    $classMetadata->getSingleAssociationReferencedJoinColumnName($field)
                );
            }
            if (is_string($type)) {
                $type = Type::getType($type);
            }
            $types[] = $type;
        }

        return $types;
    }

    /**
     * Scalar mapping should always contain unique numbers to support broken queries with duplicated column names.
     *
     * @param QueryBuilder $selectQueryBuilder
     * @return Query
     */
    protected function getQuery(QueryBuilder $selectQueryBuilder): Query
    {
        $query = $selectQueryBuilder->getQuery();
        $parser = new Query\Parser($query);
        $parseResult = $parser->parse();
        $rsm = $parseResult->getResultSetMapping();
        if ($rsm) {
            $i = 0;
            foreach ($rsm->scalarMappings as &$mapping) {
                $mapping = ++$i;
            }
            unset($mapping);
            $query->setResultSetMapping($rsm);
        }

        return $query;
    }

    /**
     * @param string $className
     * @param array $fields
     * @return array
     */
    protected function getColumns($className, array $fields)
    {
        $result = [];

        foreach ($fields as $field) {
            if (!isset($this->tablesColumns[$className][$field])) {
                $classMetadata = $this->helper->getClassMetadata($className);
                if (!$classMetadata->hasField($field) && !$classMetadata->hasAssociation($field)) {
                    throw new \InvalidArgumentException(sprintf('Field %s is not known for %s', $field, $className));
                }
                if ($classMetadata->hasAssociation($field)) {
                    $mapping = $classMetadata->getAssociationMapping($field);
                    $this->tablesColumns[$className][$field] = array_shift($mapping['joinColumnFieldNames']);
                } else {
                    $this->tablesColumns[$className][$field] = $classMetadata->getColumnName($field);
                }
            }
            $result[] = $this->tablesColumns[$className][$field];
        }

        return $result;
    }
}
