<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

/**
 * Defines the contract for handling test artifacts (screenshots, logs, etc.) and uploading them to remote storage.
 */
interface ArtifactsHandlerInterface
{
    /**
     * @param string $file
     * @return string Url to uploaded artifact
     */
    public function save($file);

    /**
     * @return string
     */
    public static function getConfigKey();
}
