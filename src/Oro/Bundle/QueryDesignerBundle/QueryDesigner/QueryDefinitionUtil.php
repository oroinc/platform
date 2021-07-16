<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;

/**
 * Provides a set of static methods to work with the query definition created by the query designer.
 */
final class QueryDefinitionUtil
{
    /**
     * Returns the JSON representation of the given query definition.
     */
    public static function encodeDefinition(array $definition): string
    {
        return json_encode($definition, JSON_THROW_ON_ERROR);
    }

    /**
     * Decodes the JSON representation of the given query definition.
     *
     * @throw InvalidConfigurationException if the JSON representation is not valid
     */
    public static function decodeDefinition(?string $encodedDefinition): array
    {
        if (!$encodedDefinition) {
            return [];
        }

        try {
            return json_decode($encodedDefinition, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidConfigurationException(
                sprintf('The query designer definition is not valid JSON: %s.', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Decodes the JSON representation of the given query definition.
     * Unlike {@see decodeDefinition()}, this method returns empty array if the given encoded definition
     * contains an invalid JSON representation or it is NULL.
     */
    public static function safeDecodeDefinition(?string $encodedDefinition): array
    {
        if (!$encodedDefinition) {
            return [];
        }

        try {
            return json_decode($encodedDefinition, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [];
        }
    }

    /**
     * Returns a string that unique identify the given column.
     */
    public static function buildColumnIdentifier(array $column): string
    {
        $result = $column['name'];
        if (!empty($column['func'])) {
            $func = $column['func'];
            $result = sprintf(
                '%s(%s,%s,%s)',
                $result,
                $func['name'],
                $func['group_name'] ?? '',
                $func['group_type'] ?? ''
            );
        }

        return $result;
    }
}
