<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Reader;

/**
 * Defines the contract for reading workflow configuration files.
 *
 * Implementations of this interface are responsible for reading and parsing workflow configuration files
 * from the filesystem. This abstraction allows different file formats (YAML, XML, etc.) to be supported
 * through different implementations. Configuration readers are used during the workflow configuration loading
 * process to retrieve raw configuration data that is subsequently processed and validated.
 */
interface ConfigFileReaderInterface
{
    public function read(\SplFileInfo $file): array;
}
