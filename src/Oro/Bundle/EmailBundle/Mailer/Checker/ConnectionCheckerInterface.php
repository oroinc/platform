<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Symfony\Component\Mailer\Transport\Dsn;

/**
 * Interface for mailer transport connection checkers.
 */
interface ConnectionCheckerInterface
{
    /**
     * Checks connection for the specified DSN.
     *
     * @param Dsn $dsn DSN to check connection for.
     * @param string|null $error Error that occurred during check.
     * @return bool True on success.
     */
    public function checkConnection(Dsn $dsn, string &$error = null): bool;

    /**
     * Checks if connection can be checked for the specified DSN.
     *
     * @param Dsn $dsn
     * @return bool
     */
    public function supports(Dsn $dsn): bool;
}
