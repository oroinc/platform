<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * This exception is thrown if the splitting a file into multiple files failed.
 */
class FileSplitterException extends \RuntimeException
{
    /** @var string[] */
    private array $targetFileNames;

    /**
     * @param string          $sourceFileName  The name of the source file
     * @param array           $targetFileNames The names of the target files that were created before the failure
     * @param \Throwable|null $previous        The previous exception used for the exception chaining
     */
    public function __construct(string $sourceFileName, array $targetFileNames, \Throwable $previous = null)
    {
        $message = sprintf('Failed to split the file "%s".', $sourceFileName);
        if (null !== $previous) {
            $message .= sprintf(' Reason: %s', $previous->getMessage());
        }
        parent::__construct($message, 0, $previous);
        $this->targetFileNames = $targetFileNames;
    }

    /**
     * @return string[]
     */
    public function getTargetFileNames(): array
    {
        return $this->targetFileNames;
    }
}
