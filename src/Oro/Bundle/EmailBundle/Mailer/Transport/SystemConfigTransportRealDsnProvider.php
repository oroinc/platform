<?php

namespace Oro\Bundle\EmailBundle\Mailer\Transport;

use Oro\Bundle\EmailBundle\Provider\AbstractSmtpSettingsProvider;
use Symfony\Component\Mailer\Transport\Dsn;

/**
 * For the "oro://system-config" DSN provides real DSN that could be used by mailer transports factories:
 * 1) DSN created from SMTP settings taken from system config, if eligible;
 * 2) fallback DSN if "fallback" option is specified, e.g. "oro://system-config?fallback=smtp://localhost:1025";
 * 3) default DSN, i.e. "native://default".
 */
class SystemConfigTransportRealDsnProvider
{
    private AbstractSmtpSettingsProvider $smtpSettingsProvider;

    private DsnFromSmtpSettingsFactory $dsnFromSmtpSettingsFactory;

    public function __construct(
        AbstractSmtpSettingsProvider $smtpSettingsProvider,
        DsnFromSmtpSettingsFactory $dsnFromSmtpSettingsFactory
    ) {
        $this->smtpSettingsProvider = $smtpSettingsProvider;
        $this->dsnFromSmtpSettingsFactory = $dsnFromSmtpSettingsFactory;
    }

    public function getRealDsn(Dsn $dsn): Dsn
    {
        $this->assertDsn($dsn);

        $smtpSettings = $this->smtpSettingsProvider->getSmtpSettings();

        if ($smtpSettings->isEligible()) {
            $dsn = $this->dsnFromSmtpSettingsFactory->create($smtpSettings);
        } else {
            $dsn = Dsn::fromString($dsn->getOption('fallback') ?: 'native://default');
        }

        return $dsn;
    }

    /**
     * Ensures that dsn is "oro://system-config".
     */
    private function assertDsn(Dsn $dsn): void
    {
        if ($dsn->getScheme() !== 'oro' || $dsn->getHost() !== 'system-config') {
            throw new \InvalidArgumentException('Dsn was expected to be "oro://system-config"');
        }
    }
}
