<?php

namespace Oro\Bundle\TranslationBundle\Helper;

use Symfony\Component\Finder\Finder;

/**
 * The helper class to work with file-based languages.
 */
class FileBasedLanguageHelper
{
    private string $fileBasedLanguagesPath;

    public function __construct(string $fileBasedLanguagesPath)
    {
        $this->fileBasedLanguagesPath = $fileBasedLanguagesPath;
    }

    /**
     * Checks if the language should be file-based.
     */
    public function isFileBasedLocale($locale): bool
    {
        // filename format is 'domain.locale.ext'
        $finder = Finder::create()
            ->files()
            ->name('*.' . $locale . '.*')
            ->in($this->fileBasedLanguagesPath);

        return $finder->count() > 0;
    }
}
