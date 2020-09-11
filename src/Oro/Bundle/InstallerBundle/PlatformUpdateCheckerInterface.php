<?php

namespace Oro\Bundle\InstallerBundle;

/**
 * An interface for classes that are used to check if an application can be updated to a new version.
 */
interface PlatformUpdateCheckerInterface
{
    /**
     * Checks if an application can be updated to a new version.
     *
     * @return string[]|null the list of messages that describe why an application is not ready to be updated
     */
    public function checkReadyToUpdate(): ?array;
}
