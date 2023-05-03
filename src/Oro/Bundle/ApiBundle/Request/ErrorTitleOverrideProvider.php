<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * The error title override provider that returns substitutions
 * configured via "error_title_overrides" section in "Resources/config/oro/app.yml" files.
 */
class ErrorTitleOverrideProvider
{
    private array $substitutions;

    /**
     * @param string[] $substitutions [error title => substitute error title, ...]
     */
    public function __construct(array $substitutions)
    {
        $this->substitutions = $substitutions;
    }

    /**
     * Returns the error title that should be used instead the given error title.
     */
    public function getSubstituteErrorTitle(string $errorTitle): ?string
    {
        return $this->substitutions[$errorTitle] ?? null;
    }
}
