<?php

namespace Oro\Component\Layout\Extension\Theme\ResourceProvider;

use Oro\Component\Config\Loader\ByFileNameMatcher;

/**
 * Implements the strategy to match layout update files.
 */
class LayoutUpdateFileMatcher extends ByFileNameMatcher
{
    /** @var string[] */
    private $excludeFilePathPatterns;

    /**
     * @param string[] $fileNamePatterns        The regular expressions to match file names
     * @param string[] $excludeFilePathPatterns The regular expressions to exclude file paths
     */
    public function __construct(array $fileNamePatterns, array $excludeFilePathPatterns)
    {
        parent::__construct($fileNamePatterns);
        $this->excludeFilePathPatterns = $excludeFilePathPatterns;
    }

    /**
     * {@inheritdoc}
     */
    public function isMatched(\SplFileInfo $file): bool
    {
        return
            parent::isMatched($file)
            && !$this->isFileExcluded($file);
    }

    public function __serialize(): array
    {
        return [parent::__serialize(), $this->excludeFilePathPatterns];
    }

    public function __unserialize(array $serialized): void
    {
        [$serializedParent, $this->excludeFilePathPatterns] = $serialized;
        parent::__unserialize($serializedParent);
    }

    private function isFileExcluded(\SplFileInfo $file): bool
    {
        if (empty($this->excludeFilePathPatterns)) {
            return false;
        }

        $filePath = $file->getPathname();
        if ('/' !== DIRECTORY_SEPARATOR) {
            $filePath = str_replace(DIRECTORY_SEPARATOR, '/', $filePath);
        }
        foreach ($this->excludeFilePathPatterns as $pattern) {
            if (preg_match($pattern, $filePath)) {
                return true;
            }
        }

        return false;
    }
}
