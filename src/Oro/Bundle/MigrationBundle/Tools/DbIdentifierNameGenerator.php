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
        return $this->generateIdentifierName($tableName, $columnNames, $uniqueIndex ? 'UNIQ' : 'IDX');
    }

    /**
     * Builds a foreign key constraint name
     *
     * @param string   $tableName
     * @param string[] $columnNames
     * @return string
     */
    public function generateForeignKeyConstraintName($tableName, $columnNames)
    {
        return $this->generateIdentifierName($tableName, $columnNames, 'FK');
    }

    /**
     * Generates an identifier from a list of column names obeying a certain string length.
     *
     * This is especially important for Oracle, since it does not allow identifiers larger than 30 chars,
     * however building identifiers automatically for foreign keys, composite keys or such can easily create
     * very long names.
     *
     * @param string|string[] $tableNames A table name or a list of table names
     * @param string[]        $columnNames
     * @param string          $prefix
     * @param bool|null       $upperCase If TRUE the returned string is in upper case;
     *                                   If FALSE the returned string is in lower case;
     *                                   If NULL the encoded name id in upper case, not encoded is in lower case
     * @return string
     * @throws \InvalidArgumentException
     */
    public function generateIdentifierName(
        $tableNames,
        $columnNames,
        $prefix = '',
        $upperCase = null
    ) {
        if (empty($tableNames) || (is_array($tableNames) && count($tableNames) === 1 && empty($tableNames[0]))) {
            throw new \InvalidArgumentException('A table name must not be empty.');
        }
        if (!is_array($tableNames)) {
            $tableNames = [$tableNames];
        }

        $columns = implode('_', $columnNames);
        $tables = implode('_', $tableNames);
        if (strlen($prefix) + strlen($tables) + strlen($columns) + 2 <= $this->getMaxIdentifierSize()) {
            $result = $prefix . '_' . $tables . '_' . $columns;

            return $upperCase === true ? strtoupper($result) : strtolower($result);
        } else {
            $result = $prefix . '_' .
                implode(
                    '',
                    array_merge(
                        array_map(
                            function ($name) {
                                return dechex(crc32($name));
                            },
                            $tableNames
                        ),
                        array_map(
                            function ($name) {
                                return dechex(crc32($name));
                            },
                            $columnNames
                        )
                    )
                );
            $result = substr($result, 0, $this->getMaxIdentifierSize());

            return $upperCase === false ? strtolower($result) : strtoupper($result);
        }
    }
}
