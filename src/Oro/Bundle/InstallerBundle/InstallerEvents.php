<?php

namespace Oro\Bundle\InstallerBundle;

/**
 * Events that may triggered during installation or update process
 */
final class InstallerEvents
{
    /**
     * The installer.database_preparation.before event is thrown before the database is ready to use.
     *
     * @var string
     */
    const INSTALLER_BEFORE_DATABASE_PREPARATION = 'installer.database_preparation.before';

    /**
     * The installer.database_preparation.after event is thrown upon completion of a database preparation process.
     *
     * @var string
     */
    const INSTALLER_AFTER_DATABASE_PREPARATION = 'installer.database_preparation.after';

    /**
     * The installer.finish event is thrown upon completion of a final step of the installation process.
     *
     * @var string
     */
    const FINISH = 'installer.finish';
}
