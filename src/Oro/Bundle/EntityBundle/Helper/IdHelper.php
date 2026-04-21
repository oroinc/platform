<?php

namespace Oro\Bundle\EntityBundle\Helper;

/**
 * Helper for manipulation with id's strings and sequences
 */
class IdHelper
{
    private const string SEQUENCE_SEPARATOR = ',';

    /**
     * Generates postgres notation sequence({id1, id2, id3}) from array of ids
     * for using in functions like ANY
     *
     * @param array $identifiers
     * @return string
     */
    public static function getIdsSequence(array $identifiers): string
    {
        $idsString = implode(self::SEQUENCE_SEPARATOR, $identifiers);
        return sprintf('{%s}', $idsString);
    }
}
