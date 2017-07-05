<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Reader;

interface ConfigFileReaderInterface
{
    /**
     * @param \SplFileInfo $file
     *
     * @return array
     */
    public function read(\SplFileInfo $file): array;
}
