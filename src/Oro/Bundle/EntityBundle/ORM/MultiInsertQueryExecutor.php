<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

/**
 * This executor should be used only for queries that will reduce result count after each execution by itself
 * Be aware that BufferedQueryResultIterator won't work correct for such queries, because it uses SKIP, LIMIT operators
 *
 * Additionally note that it's recommended to use this executor instead of 'insert from select' in mysql, because of
 * innodb auto increment table lock that force to wait to insert request until another insert on the same table be done.
 */
class MultiInsertQueryExecutor implements InsertQueryExecutorInterface
{
    /**
     * @var int
     */
    private $batchSize = 5000;

    /**
     * @var NativeQueryExecutorHelper
     */
    private $helper;

    public function __construct(NativeQueryExecutorHelper $helper)
    {
        $this->helper = $helper;
    }

    public function setBatchSize(int $batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     *{@inheritDoc}
     */
    public function execute(string $className, array $fields, QueryBuilder $selectQueryBuilder):int
    {
        $connection = $this->helper->getManager($className)->getConnection();
        $insertToTableName = $this->helper->getTableName($className);

        $columns = $this->helper->getColumns($className, $fields);
        $columnTypes = $this->getColumnTypes($className, $fields);

        $columnNamesWithNonNamedParameters = array_combine(
            $columns,
            array_fill(0, count($columns), '?')
        );

        $insert = $connection->createQueryBuilder()
            ->insert($insertToTableName)
            ->values($columnNamesWithNonNamedParameters);

        $total = 0;
        $iterator = new BufferedIdentityQueryResultIterator($this->getQuery($selectQueryBuilder));
        $iterator->setBufferSize($this->batchSize);

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
     */
    protected function getQuery(QueryBuilder $selectQueryBuilder): Query
    {
        $query = $selectQueryBuilder->getQuery();
        $rsm = QueryUtil::parseQuery($query)->getResultSetMapping();
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
}
