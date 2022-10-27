<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * The error title override provider that returns substitutions
 * configured via "error_title_overrides" section in "Resources/config/oro/app.yml" files.
 */
class ErrorTitleOverrideProvider
{
    /** @var string[] [error title => substitute error title, ...] */
    private $substitutions;

    /**
     * @param string[] $substitutions
     */
    public function __construct(array $substitutions)
    {
        $this->substitutions = $substitutions;
    }

    /**
     * Returns the error title that should be used instead the given error title.
     *
     * @param string $errorTitle
     *
     * @return string|null The error title that substitutes the given error title
     *                     or NULL if there is no substitution
     */
    public function getSubstituteErrorTitle(string $errorTitle): ?string
    {
        return $this->substitutions[$errorTitle] ?? null;
    }
}
