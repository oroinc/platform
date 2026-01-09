<?php

namespace Oro\Component\DoctrineUtils\DBAL;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Utility class for parsing SQL queries.
 * Provides compatibility layer for functionality removed from Doctrine DBAL 3.x.
 *
 * This class replicates the essential functionality of the removed Doctrine\DBAL\SQLParserUtils
 * for internal use in Oro components.
 */
class SqlParserUtils
{
    /**
     * Expands array parameters in a positional SQL query.
     *
     * For a positional query this method can rewrite the sql statement with regard to array parameters.
     *
     * @param string                              $query  The SQL query to expand
     * @param array<int, mixed>                   $params The parameters
     * @param array<int, int|string|ParameterType|ArrayParameterType> $types  The parameter types
     *
     * @return array{0: string, 1: list<mixed>, 2: array<int, int|string>}
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function expandListParameters(string $query, array $params, array $types): array
    {
        $newParams = [];
        $newTypes = [];
        $newQuery = '';

        $queryOffset = 0;
        $paramIndex = 0;

        while (($pos = self::getNextPlaceholderPosition($query, $queryOffset)) !== null) {
            $newQuery .= substr($query, $queryOffset, $pos - $queryOffset);

            if (!array_key_exists($paramIndex, $params)) {
                $newQuery .= '?';
                $queryOffset = $pos + 1;
                $paramIndex++;
                continue;
            }

            $param = $params[$paramIndex];
            $type = $types[$paramIndex] ?? ParameterType::STRING;

            // Check if this is an array parameter type
            if (self::isArrayParameterType($type)) {
                if (!is_array($param)) {
                    $param = [$param];
                }

                if (count($param) === 0) {
                    // Empty array - replace with NULL
                    $newQuery .= 'NULL';
                } else {
                    // Expand to multiple placeholders
                    $placeholders = [];
                    foreach ($param as $value) {
                        $placeholders[] = '?';
                        $newParams[] = $value;
                        $newTypes[] = self::getScalarType($type);
                    }
                    $newQuery .= implode(', ', $placeholders);
                }
            } else {
                $newQuery .= '?';
                $newParams[] = $param;
                $newTypes[] = $type;
            }

            $queryOffset = $pos + 1;
            $paramIndex++;
        }

        $newQuery .= substr($query, $queryOffset);

        return [$newQuery, $newParams, $newTypes];
    }

    /**
     * Gets an array of the positions of the placeholders in an sql statements.
     *
     * Returns an array of placeholder positions.
     *
     * @return list<int>
     */
    public static function getPlaceholderPositions(string $query): array
    {
        $positions = [];
        $offset = 0;

        while (($pos = self::getNextPlaceholderPosition($query, $offset)) !== null) {
            $positions[] = $pos;
            $offset = $pos + 1;
        }

        return $positions;
    }

    /**
     * Finds the next placeholder position in the query.
     *
     * @param string $query  The SQL query
     * @param int    $offset The offset to start searching from
     *
     * @return int|null The position of the next placeholder or null if none found
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private static function getNextPlaceholderPosition(string $query, int $offset): ?int
    {
        $inString = false;
        $stringChar = null;
        $escaped = false;
        $length = strlen($query);

        for ($i = $offset; $i < $length; $i++) {
            $char = $query[$i];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if ($inString) {
                if ($char === $stringChar) {
                    // Check for doubled quote (escape mechanism in SQL)
                    if ($i + 1 < $length && $query[$i + 1] === $stringChar) {
                        $i++; // Skip the next character
                        continue;
                    }
                    $inString = false;
                    $stringChar = null;
                }
                continue;
            }

            // Check for string literals
            if ($char === "'" || $char === '"') {
                $inString = true;
                $stringChar = $char;
                continue;
            }

            // Check for placeholder
            if ($char === '?') {
                return $i;
            }

            // Skip line comments
            if ($char === '-' && $i + 1 < $length && $query[$i + 1] === '-') {
                // Skip to end of line
                $eol = strpos($query, "\n", $i);
                if ($eol === false) {
                    break; // No more content
                }
                $i = $eol;
                continue;
            }

            // Skip block comments
            if ($char === '/' && $i + 1 < $length && $query[$i + 1] === '*') {
                // Skip to end of comment
                $endComment = strpos($query, '*/', $i + 2);
                if ($endComment === false) {
                    break; // No more content
                }
                $i = $endComment + 1;
                continue;
            }
        }

        return null;
    }

    /**
     * Checks if the given type is an array parameter type.
     *
     * @param mixed $type The type to check
     *
     * @return bool
     */
    private static function isArrayParameterType($type): bool
    {
        if ($type instanceof ArrayParameterType) {
            return true;
        }

        // Check for legacy DBAL 2.x array parameter type constants
        if (defined('Doctrine\DBAL\Connection::PARAM_INT_ARRAY') && $type === Connection::PARAM_INT_ARRAY) {
            return true;
        }

        if (defined('Doctrine\DBAL\Connection::PARAM_STR_ARRAY') && $type === Connection::PARAM_STR_ARRAY) {
            return true;
        }

        // DBAL 3.x enum values
        if (is_object($type) && method_exists($type, 'name')) {
            $name = $type->name;
            return $name === 'INTEGER' || $name === 'STRING';
        }

        return false;
    }

    /**
     * Gets the scalar type from an array parameter type.
     *
     * @param mixed $arrayType The array type
     *
     * @return int|string
     */
    private static function getScalarType($arrayType)
    {
        if ($arrayType instanceof ArrayParameterType) {
            // Map ArrayParameterType to scalar ParameterType
            return match ($arrayType) {
                ArrayParameterType::INTEGER => ParameterType::INTEGER,
                ArrayParameterType::STRING => ParameterType::STRING,
                ArrayParameterType::BINARY => ParameterType::BINARY,
                ArrayParameterType::ASCII => ParameterType::ASCII,
                default => ParameterType::STRING,
            };
        }

        // Handle legacy Connection::PARAM_*_ARRAY constants
        if (defined('Doctrine\DBAL\Connection::PARAM_INT_ARRAY') && $arrayType === Connection::PARAM_INT_ARRAY) {
            return ParameterType::INTEGER;
        }

        if (defined('Doctrine\DBAL\Connection::PARAM_STR_ARRAY') && $arrayType === Connection::PARAM_STR_ARRAY) {
            return ParameterType::STRING;
        }

        return ParameterType::STRING;
    }
}
