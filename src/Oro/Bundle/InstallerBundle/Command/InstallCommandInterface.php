<?php

namespace Oro\Bundle\InstallerBundle\Command;

/**
 * Marker interface for installation commands.
 *
 * This interface serves as a tagging mechanism to identify commands that are part of
 * the application installation process. Commands implementing this interface are
 * recognized by the installer framework and can be executed as part of the installation
 * workflow. This allows for flexible registration and discovery of installation-related
 * commands without requiring explicit configuration.
 */
interface InstallCommandInterface
{
}
