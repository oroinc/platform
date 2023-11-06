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
    public const INSTALLER_BEFORE_DATABASE_PREPARATION = 'installer.database_preparation.before';

    /**
     * The installer.database_preparation.after event is thrown upon completion of a database preparation process.
     *
     * @var string
     */
    public const INSTALLER_AFTER_DATABASE_PREPARATION = 'installer.database_preparation.after';

    /**
     * The installer.finish event is thrown upon completion of a final step of the installation process.
     *
     * @var string
     */
    public const FINISH = 'installer.finish';

    /**
     * The installer.initialize event is thrown upon initialize process.
     */
    public const INITIALIZE = 'installer.initialize';
}
