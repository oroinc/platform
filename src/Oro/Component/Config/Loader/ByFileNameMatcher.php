<?php

namespace Oro\Component\Config\Loader;

/**
 * Implements the strategy to match a file by one or several file name patterns.
 */
class ByFileNameMatcher implements FileMatcherInterface
{
    /** @var string[] */
    private $fileNamePatterns;

    /**
     * @param string[] $fileNamePatterns The file name regular expressions
     */
    public function __construct(array $fileNamePatterns)
    {
        $this->fileNamePatterns = $fileNamePatterns;
    }

    /**
     * {@inheritdoc}
     */
    public function isMatched(\SplFileInfo $file): bool
    {
        if (!$this->fileNamePatterns) {
            return true;
        }

        $fileName = $file->getBasename();
        foreach ($this->fileNamePatterns as $pattern) {
            if (preg_match($pattern, $fileName)) {
                return true;
            }
        }

        return false;
    }

    public function __serialize(): array
    {
        return $this->fileNamePatterns;
    }

    public function __unserialize(array $serialized): void
    {
        $this->fileNamePatterns = $serialized;
    }
}
