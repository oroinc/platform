<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Oro\Bundle\EmailBundle\Mailer\Transport\SystemConfigTransportRealDsnProvider;
use Symfony\Component\Mailer\Transport\Dsn;

/**
 * Checks connection for "oro://system-config" DSN.
 */
class SystemConfigConnectionChecker implements ConnectionCheckerInterface
{
    public function __construct(
        private readonly ConnectionCheckerInterface $connectionCheckers,
        private readonly SystemConfigTransportRealDsnProvider $systemConfigTransportRealDsnProvider
    ) {
    }

    #[\Override]
    public function supports(Dsn $dsn): bool
    {
        return $dsn->getScheme() === 'oro' && $dsn->getHost() === 'system-config';
    }

    #[\Override]
    public function checkConnection(Dsn $dsn): bool
    {
        return $this->connectionCheckers->checkConnection(
            $this->systemConfigTransportRealDsnProvider->getRealDsn($dsn)
        );
    }
}
