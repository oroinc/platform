<?php

namespace Oro\Bundle\ImapBundle\Mailer\Transport;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Creates mailer transport that uses SMTP settings from {@see \Oro\Bundle\ImapBundle\Entity\UserEmailOrigin}.
 */
class UserEmailOriginTransportFactory implements TransportFactoryInterface
{
    public const DSN = 'oro://user-email-origin';

    private Transport $transportFactory;

    private ManagerRegistry $managerRegistry;

    private DsnFromUserEmailOriginFactory $dsnFromUserEmailOriginFactory;

    private ?RequestStack $requestStack;

    public function __construct(
        Transport $transportFactory,
        ManagerRegistry $managerRegistry,
        DsnFromUserEmailOriginFactory $dsnFromUserEmailOriginFactory,
        ?RequestStack $requestStack = null
    ) {
        $this->transportFactory = $transportFactory;
        $this->managerRegistry = $managerRegistry;
        $this->dsnFromUserEmailOriginFactory = $dsnFromUserEmailOriginFactory;
        $this->requestStack = $requestStack;
    }

    public function create(Dsn $dsn): TransportInterface
    {
        return new UserEmailOriginTransport(
            $this->transportFactory,
            $this->managerRegistry,
            $this->dsnFromUserEmailOriginFactory,
            $this->requestStack
        );
    }

    /**
     * Checks that dsn is "oro://user-email-origin".
     */
    public function supports(Dsn $dsn): bool
    {
        return $dsn->getScheme() === 'oro' && $dsn->getHost() === 'user-email-origin';
    }
}
