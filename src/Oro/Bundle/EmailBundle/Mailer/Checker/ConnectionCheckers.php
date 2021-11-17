<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Symfony\Component\Mailer\Transport\Dsn;

/**
 * Makes use of inner connection checkers to check connection for the specified DSN.
 */
class ConnectionCheckers implements ConnectionCheckerInterface
{
    /** @var iterable<ConnectionCheckerInterface> */
    private iterable $connectionCheckers;

    public function __construct(iterable $connectionCheckers)
    {
        $this->connectionCheckers = $connectionCheckers;
    }

    public function supports(Dsn $dsn): bool
    {
        foreach ($this->connectionCheckers as $connectionChecker) {
            if ($connectionChecker->supports($dsn)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function checkConnection(Dsn $dsn, string &$error = null): bool
    {
        foreach ($this->connectionCheckers as $connectionChecker) {
            if ($connectionChecker->supports($dsn) && $connectionChecker->checkConnection($dsn, $error)) {
                return true;
            }
        }

        return false;
    }
}
