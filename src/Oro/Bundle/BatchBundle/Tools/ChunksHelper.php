<?php

namespace Oro\Bundle\BatchBundle\Tools;

/**
 * Contains handy methods for working with data collections which should be processed in chunks.
 */
class ChunksHelper
{
    /**
     * @param iterable $data Data to split in chunks.
     * @param int $chunkSize
     * @return iterable
     */
    public static function splitInChunks(iterable $data, int $chunkSize): iterable
    {
        $counter = 0;
        $chunk = [];
        foreach ($data as $key => $value) {
            $counter++;
            $chunk[] = $value;

            if (($counter % $chunkSize) === 0) {
                yield ($counter / $chunkSize) => $chunk;
                $chunk = [];
            }
        }

        if ($chunk) {
            yield ((int) ceil($counter / $chunkSize)) => $chunk;
        }
    }

    /**
     * Splits in chunks and preserves original keys.
     *
     * @param iterable $data Data to split in chunks.
     * @param int $chunkSize
     * @return iterable
     */
    public static function splitInChunksAssoc(iterable $data, int $chunkSize): iterable
    {
        $counter = 0;
        $chunk = [];
        foreach ($data as $key => $value) {
            $counter++;
            $chunk[$key] = $value;

            if (($counter % $chunkSize) === 0) {
                yield ($counter / $chunkSize) => $chunk;
                $chunk = [];
            }
        }

        if ($chunk) {
            yield ((int) ceil($counter / $chunkSize)) => $chunk;
        }
    }

    /**
     * @param iterable $data Data to split in chunks.
     * @param int $chunkSize
     * @param string|int $columnName Column to use as value when adding to a chunk.
     * @return iterable
     */
    public static function splitInChunksByColumn(iterable $data, int $chunkSize, $columnName): iterable
    {
        $counter = 0;
        $chunk = [];
        foreach ($data as $key => $value) {
            $counter++;
            $chunk[] = $value[$columnName];

            if (($counter % $chunkSize) === 0) {
                yield ($counter / $chunkSize) => $chunk;
                $chunk = [];
            }
        }

        if ($chunk) {
            yield ((int) ceil($counter / $chunkSize)) => $chunk;
        }
    }
}
