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
     */
    public function checkConnection(Dsn $dsn): bool;

    /**
     * Checks if connection can be checked for the specified DSN.
     */
    public function supports(Dsn $dsn): bool;
}
