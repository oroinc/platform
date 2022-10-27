<?php

namespace Oro\Bundle\AttachmentBundle\Manager\FileRemoval;

/**
 * Extracts a directory a file path starts with by a regular expression.
 *
 * The regular expression must be in the following format:
 * /^(directory match expression)\/file match expression/
 * The important rules are:
 * * the directory match expression must be enclosed by parentheses (round brackets)
 * * the matching directory must be at the begin of the file path (the regular expression must starts with /^( )
 * * the directory and file match expressions must be delimited by the slash character (/)
 *   and this slash must not be a part of the directory match expression
 */
final class DirectoryExtractor implements DirectoryExtractorInterface
{
    /** @var string */
    private $regex;

    /** @var bool */
    private $allowedToUseForSingleFile;

    public function __construct(string $regex, bool $allowedToUseForSingleFile)
    {
        $this->assertRegexValid($regex);
        $this->regex = $regex;
        $this->allowedToUseForSingleFile = $allowedToUseForSingleFile;
    }

    /**
     * {@inheritDoc}
     */
    public function extract(string $path): ?string
    {
        $matches = [];
        preg_match($this->regex, $path, $matches);
        $dir = $matches[1] ?? null;
        if ($dir) {
            return $dir;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowedToUseForSingleFile(): bool
    {
        return $this->allowedToUseForSingleFile;
    }

    private function assertRegexValid(string $regex): void
    {
        if (strncmp($regex, '/^(', 3) !== 0) {
            throw new \LogicException(sprintf(
                'The expression must starts with "/^(". Expression: %s.',
                $regex
            ));
        }
        if (strncmp($regex, '/^(\/', 5) === 0) {
            throw new \LogicException(sprintf(
                'The directory match expression must not starts with "/". Expression: %s.',
                $regex
            ));
        }
    }
}
