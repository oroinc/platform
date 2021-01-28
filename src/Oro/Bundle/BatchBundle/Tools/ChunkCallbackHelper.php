<?php

namespace Oro\Bundle\BatchBundle\Tools;

/**
 * Contains handy methods for working with data collections which should be processed in chunks.
 */
class ChunkCallbackHelper
{
    /**
     * Goes through $dataSet, fills chunk items with $chunkItemCallback and process the filled chunk
     * in $chunkProcessCallback.
     *
     * @param iterable $dataSet Data to split in chunks and process.
     * @param callable $chunkItemCallback Callback used for filling $chunk with items.
     * @param callable $chunkProcessCallback Callback used to process the filled chunk.
     * @param int $size
     * @return int
     */
    public static function processInChunks(
        iterable $dataSet,
        callable $chunkItemCallback,
        callable $chunkProcessCallback,
        int $size
    ): int {
        if ($size <= 0) {
            return 0;
        }

        $count = 0;
        $chunk = [];
        foreach ($dataSet as $dataItemKey => $dataItemValue) {
            $count++;

            $chunkItemCallback($chunk, $dataItemValue, $dataItemKey);

            if (($count % $size) === 0) {
                $chunkProcessCallback($chunk, $count / $size);
                $chunk = [];
            }
        }

        if ($count % $size) {
            $chunkProcessCallback($chunk, ceil($count / $size));
        }

        return $count;
    }
}
