<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

/**
 * Artifacts handler that stores any file, not only a screenshot, and gives the public base URL of its storage.
 * The failure report and the Playwright trace links use it. Other handlers store screenshots only.
 */
interface ArtifactsFileHandlerInterface extends ArtifactsHandlerInterface
{
    /**
     * Saves the content under the base name of $fileName.
     * The caller must make the name unique, because parallel jobs can share the storage.
     *
     * @return string URL of the uploaded artifact
     */
    public function saveFile(string $content, string $fileName): string;

    /**
     * Returns the public base URL of the storage with a trailing slash, or null when none is configured.
     */
    public function getBaseUrl(): ?string;
}
