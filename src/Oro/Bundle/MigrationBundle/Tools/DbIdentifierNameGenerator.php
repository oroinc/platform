<?php

namespace Oro\Bundle\MigrationBundle\Tools;

class DbIdentifierNameGenerator
{
    /**
     * Gets the max size of an identifier
     *
     * @return int
     */
    public function getMaxIdentifierSize()
    {
        return 30;
    }

    /**
     * Builds an index name
     *
     * @param string   $tableName
     * @param string[] $columnNames
     * @param bool     $uniqueIndex
     * @return string
     */
    public function generateIndexName($tableName, $columnNames, $uniqueIndex = false)
    {
        return $this->generateIdentifierName($columnNames, $uniqueIndex ? 'UIDX' : 'IDX', [$tableName]);
    }

    /**
     * Builds a foreign key constraint name
     *
     * @param string   $tableName
     * @param string[] $columnNames
     * @param string   $foreignTableName
     * @param string[] $foreignColumnNames
     * @return string
     */
    public function generateForeignKeyConstraintName($tableName, $columnNames, $foreignTableName, $foreignColumnNames)
    {
        return $this->generateIdentifierName(
            array_merge($columnNames, $foreignColumnNames),
            'FK',
            [$tableName, $foreignTableName]
        );
    }

    /**
     * Generates an identifier from a list of column names obeying a certain string length.
     *
     * This is especially important for Oracle, since it does not allow identifiers larger than 30 chars,
     * however building identifiers automatically for foreign keys, composite keys or such can easily create
     * very long names.
     *
     * @param string[]        $columnNames
     * @param string          $prefix
     * @param string|string[] $tableNames  A table name or a list of table names
     * @return string
     */
    public function generateIdentifierName(
        $columnNames,
        $prefix = '',
        $tableNames = null
    ) {
        if (empty($tableNames)) {
            $result = $prefix . '_' .
                implode(
                    '',
                    array_map(
                        function ($name) {
                            return dechex(crc32($name));
                        },
                        $columnNames
                    )
                );
            $result = substr($result, 0, $this->getMaxIdentifierSize());
        } else {
            if (!is_array($tableNames)) {
                $tableNames = [$tableNames];
            }
            $columns = implode('_', $columnNames);
            $tables = implode('_', $tableNames);
            if (strlen($prefix) + strlen($tables) + strlen($columns) + 2 <= $this->getMaxIdentifierSize()) {
                $result = $prefix . '_' . $tables . '_' . $columns;
            } else {
                $result = $prefix . '_' .
                    implode(
                        '',
                        array_merge(
                            array_map(
                                function ($name) {
                                    return dechex(crc32($name));
                                },
                                $columnNames
                            ),
                            array_map(
                                function ($name) {
                                    return dechex(crc32($name));
                                },
                                $tableNames
                            )
                        )
                    );
                $result = substr($result, 0, $this->getMaxIdentifierSize());
            }
        }

        return strtolower($result);
    }
}
