<?php

namespace Oro\Bundle\EmailBundle\Mailer\Transport;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Creates mailer transport that uses either SMTP settings from system config or a fallback transport.
 */
class SystemConfigTransportFactory implements TransportFactoryInterface
{
    use ConfigureLocalDomainTrait;

    private Transport $transportFactory;

    private SystemConfigTransportRealDsnProvider $systemConfigTransportRealDsnProvider;

    private ?RequestStack $requestStack;

    public function __construct(
        Transport $transportFactory,
        SystemConfigTransportRealDsnProvider $systemConfigTransportRealDsnProvider,
        ?RequestStack $requestStack = null
    ) {
        $this->transportFactory = $transportFactory;
        $this->systemConfigTransportRealDsnProvider = $systemConfigTransportRealDsnProvider;
        $this->requestStack = $requestStack;
    }

    public function create(Dsn $dsn): TransportInterface
    {
        $realDsn = $this->systemConfigTransportRealDsnProvider->getRealDsn($dsn);
        $transport = $this->transportFactory->fromDsnObject($realDsn);
        $this->configureLocalDomain($transport, $this->requestStack);

        return $transport;
    }

    /**
     * Checks that dsn is "oro://system-config".
     */
    public function supports(Dsn $dsn): bool
    {
        return $dsn->getScheme() === 'oro' && $dsn->getHost() === 'system-config';
    }
}
