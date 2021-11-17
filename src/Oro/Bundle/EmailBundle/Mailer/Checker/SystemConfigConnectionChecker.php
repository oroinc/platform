<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Oro\Bundle\EmailBundle\Mailer\Transport\SystemConfigTransportRealDsnProvider;
use Symfony\Component\Mailer\Transport\Dsn;

/**
 * Checks connection for "oro://system-config" DSN.
 */
class SystemConfigConnectionChecker implements ConnectionCheckerInterface
{
    private ConnectionCheckerInterface $connectionCheckers;

    private SystemConfigTransportRealDsnProvider $systemConfigTransportRealDsnProvider;

    public function __construct(
        ConnectionCheckerInterface $connectionCheckers,
        SystemConfigTransportRealDsnProvider $systemConfigTransportRealDsnProvider
    ) {
        $this->connectionCheckers = $connectionCheckers;
        $this->systemConfigTransportRealDsnProvider = $systemConfigTransportRealDsnProvider;
    }

    /**
     * Checks that dsn is "oro://system-config".
     */
    public function supports(Dsn $dsn): bool
    {
        return $dsn->getScheme() === 'oro' && $dsn->getHost() === 'system-config';
    }

    /**
     * {@inheritdoc}
     */
    public function checkConnection(Dsn $dsn, string &$error = null): bool
    {
        $realDsn = $this->systemConfigTransportRealDsnProvider->getRealDsn($dsn);

        return $this->connectionCheckers->checkConnection($realDsn, $error);
    }
}
