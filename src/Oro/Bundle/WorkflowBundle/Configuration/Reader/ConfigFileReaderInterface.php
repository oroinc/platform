<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Reader;

interface ConfigFileReaderInterface
{
    public function read(\SplFileInfo $file): array;
}
