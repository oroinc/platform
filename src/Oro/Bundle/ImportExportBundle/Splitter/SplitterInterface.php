<?php

namespace Oro\Bundle\ImportExportBundle\Splitter;

/**
 * Interface SplitterInterface
 *
 * The goal of the classes implement this interface is to split the importing file to the smaller files
 * for the import palatalization.
 */
interface SplitterInterface
{
    /**
     * Reads the given file and returns an array of the chunk files paths.
     *
     * @param $pathFile string
     *
     * @return string[]
     */
    public function getSplittedFilesNames($pathFile);

    /**
     * Returns an array of the errors - messages of the exceptions thrown while splitting the file
     *
     * @return string[]
     */
    public function getErrors();
}
